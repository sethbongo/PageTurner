<?php

namespace App\Http\Controllers;

use App\Models\ImportExportLog;
use App\Models\AuditLog;
use App\Models\ApiRateLimit;
use App\Models\BackupMonitoring;
use App\Models\ScheduledTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function index()
    {
        // Get import/export status data
        $importExportStatus = $this->getImportExportStatus();

        // Get backup status data
        $backupStatus = $this->getBackupStatus();

        // Get audit log summary
        $auditLogSummary = $this->getAuditLogSummary();

        // Get API usage statistics
        $apiUsageStats = $this->getApiUsageStatistics();

        // Get system health metrics
        $systemHealth = $this->getSystemHealth();

        return view('admin.dashboard.enhanced', compact(
            'importExportStatus',
            'backupStatus',
            'auditLogSummary',
            'apiUsageStats',
            'systemHealth'
        ));
    }

    /**
     * Get import/export status data
     */
    protected function getImportExportStatus(): array
    {
        $cachedData = Cache::get('admin_import_export_status');
        if ($cachedData) {
            return $cachedData;
        }

        $recentOperations = ImportExportLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Queue status summary
        $queueStatus = ImportExportLog::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Success/failure rates
        $totalOps = ImportExportLog::count();
        $successOps = ImportExportLog::where('status', 'completed')->count();
        $failedOps = ImportExportLog::where('status', 'failed')->count();

        $successRate = $totalOps > 0 ? ($successOps / $totalOps) * 100 : 0;
        $failureRate = $totalOps > 0 ? ($failedOps / $totalOps) * 100 : 0;

        $data = [
            'recent_operations' => $recentOperations,
            'queue_status' => $queueStatus,
            'total_operations' => $totalOps,
            'success_rate' => round($successRate, 2),
            'failure_rate' => round($failureRate, 2),
            'pending_count' => $queueStatus['pending'] ?? 0,
            'processing_count' => $queueStatus['processing'] ?? 0,
        ];

        Cache::put('admin_import_export_status', $data, now()->addMinutes(5));
        return $data;
    }

    /**
     * Get backup status data
     */
    protected function getBackupStatus(): array
    {
        $cachedData = Cache::get('admin_backup_status');
        if ($cachedData) {
            return $cachedData;
        }

        $latestBackup = BackupMonitoring::orderBy('completed_at', 'desc')
            ->first();

        $backupHistory = BackupMonitoring::orderBy('completed_at', 'desc')
            ->limit(10)
            ->get();

        $healthySummary = BackupMonitoring::selectRaw('health_status, COUNT(*) as count')
            ->groupBy('health_status')
            ->pluck('count', 'health_status')
            ->toArray();

        $data = [
            'latest_backup' => $latestBackup,
            'backup_history' => $backupHistory,
            'health_summary' => $healthySummary,
            'is_healthy' => $latestBackup?->isHealthy() ?? false,
            'is_stale' => $latestBackup?->isStale() ?? true,
            'storage_used' => $latestBackup?->getFormattedSize() ?? 'N/A',
        ];

        Cache::put('admin_backup_status', $data, now()->addMinutes(10));
        return $data;
    }

    /**
     * Get audit log summary
     */
    protected function getAuditLogSummary(): array
    {
        $cachedData = Cache::get('admin_audit_log_summary');
        if ($cachedData) {
            return $cachedData;
        }

        // Recent critical events
        $criticalEvents = AuditLog::with(['user'])
            ->whereRaw("JSON_EXTRACT(metadata, '$.severity') = 'critical'")
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        // Security alerts summary
        $alertsSummary = AuditLog::selectRaw('event, COUNT(*) as count')
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();

        // User activity
        $activeUsers = AuditLog::distinct('user_id')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        // Events today
        $eventsToday = AuditLog::where('created_at', '>=', now()->startOfDay())
            ->count();

        $data = [
            'critical_events' => $criticalEvents,
            'alerts_summary' => $alertsSummary,
            'active_users_24h' => $activeUsers,
            'events_today' => $eventsToday,
            'total_logs' => AuditLog::count(),
        ];

        Cache::put('admin_audit_log_summary', $data, now()->addMinutes(5));
        return $data;
    }

    /**
     * Get API usage statistics
     */
    protected function getApiUsageStatistics(): array
    {
        $cachedData = Cache::get('admin_api_usage_stats');
        if ($cachedData) {
            return $cachedData;
        }

        // Requests by endpoint
        $requestsByEndpoint = ApiRateLimit::selectRaw('endpoint, SUM(requests_count) as total')
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('endpoint')
            ->orderByRaw('SUM(requests_count) DESC')
            ->limit(15)
            ->pluck('total', 'endpoint')
            ->toArray();

        // Rate limit hits
        $rateLimitHits = ApiRateLimit::where('rate_limited', true)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        // Error rates by endpoint
        $errorsByEndpoint = ApiRateLimit::selectRaw('endpoint, COUNT(*) as error_count')
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('endpoint')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->pluck('error_count', 'endpoint')
            ->toArray();

        // Top users by requests
        $topUsers = ApiRateLimit::with('user')
            ->selectRaw('user_id, SUM(requests_count) as total')
            ->where('created_at', '>=', now()->subHours(24))
            ->where('user_id', '!=', null)
            ->groupBy('user_id')
            ->orderByRaw('SUM(requests_count) DESC')
            ->limit(10)
            ->get();

        // Total requests today
        $totalRequests = ApiRateLimit::where('created_at', '>=', now()->startOfDay())
            ->sum('requests_count');

        $data = [
            'requests_by_endpoint' => $requestsByEndpoint,
            'rate_limit_hits_24h' => $rateLimitHits,
            'errors_by_endpoint' => $errorsByEndpoint,
            'top_users' => $topUsers,
            'total_requests_today' => $totalRequests,
        ];

        Cache::put('admin_api_usage_stats', $data, now()->addMinutes(5));
        return $data;
    }

    /**
     * Get system health metrics
     */
    protected function getSystemHealth(): array
    {
        $cachedData = Cache::get('admin_system_health');
        if ($cachedData) {
            return $cachedData;
        }

        // Database size (rough estimate using table sizes)
        $dbSize = DB::select(DB::raw(
            "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
             FROM information_schema.tables 
             WHERE table_schema = DATABASE()"
        ))[0]->size_mb ?? 0;

        // Storage usage estimate
        $storageUsed = $this->getStorageUsage();

        // Queue lengths
        $queueLengths = [
            'import_export' => ImportExportLog::whereIn('status', ['pending', 'processing'])->count(),
            'backups' => ScheduledTask::where('type', 'backup')->where('enabled', true)->count(),
        ];

        // Failed job counts
        $failedJobs = DB::table('failed_jobs')->count();

        $data = [
            'database_size_mb' => round($dbSize, 2),
            'storage_used_mb' => $storageUsed,
            'queue_lengths' => $queueLengths,
            'failed_jobs' => $failedJobs,
            'timestamp' => now(),
        ];

        Cache::put('admin_system_health', $data, now()->addMinutes(5));
        return $data;
    }

    /**
     * Calculate storage usage in MB
     */
    protected function getStorageUsage(): float
    {
        $path = storage_path('app');
        $total = 0;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $total += $file->getSize();
            }
        }

        return round($total / 1024 / 1024, 2);
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache()
    {
        Cache::forget('admin_import_export_status');
        Cache::forget('admin_backup_status');
        Cache::forget('admin_audit_log_summary');
        Cache::forget('admin_api_usage_stats');
        Cache::forget('admin_system_health');

        return back()->with('success', 'Dashboard cache cleared.');
    }
}
