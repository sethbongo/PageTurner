<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AuditLogsExport;

class AuditController extends Controller
{
    /**
     * Show audit logs dashboard
     */
    public function index(Request $request)
    {
        $this->authorize('isAdmin');

        $query = AuditLog::with('user');

        // Filter by event type
        if ($request->filled('event')) {
            $query->byEvent($request->event);
        }

        // Filter by model type
        if ($request->filled('model_type')) {
            $query->byModel($request->model_type);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by date range
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->dateRange(
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_to)->endOfDay()
            );
        }

        // Filter by search term (in metadata)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereJsonContains('metadata->ip_address', $search)
                    ->orWhereJsonContains('metadata->url', $search)
                    ->orWhereRaw("JSON_EXTRACT(metadata, '$.url') LIKE ?", ["%{$search}%"]);
            });
        }

        // Filter for critical events
        if ($request->boolean('critical_only')) {
            $query->whereIn('event', [
                'role_changed',
                '2fa_disabled',
                'password_changed',
                'force_deleted',
                'backup_failed',
            ]);
        }

        // Include archived logs or not
        if (!$request->boolean('show_archived')) {
            $query->active();
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->paginate(50);

        // Get unique event types for filter dropdown
        $eventTypes = AuditLog::distinct('event')->pluck('event');

        // Get model types for filter dropdown
        $modelTypes = AuditLog::distinct('auditable_type')->pluck('auditable_type');

        // Get users for filter dropdown
        $users = User::orderBy('email')->pluck('email', 'id');

        return view('admin.audit.index', compact(
            'auditLogs',
            'eventTypes',
            'modelTypes',
            'users'
        ));
    }

    /**
     * Show detailed audit log entry
     */
    public function show(AuditLog $auditLog)
    {
        $this->authorize('isAdmin');

        // Verify checksum integrity
        $service = app(\App\Services\AuditService::class);
        $isValid = $service->verifyChecksum($auditLog);

        return view('admin.audit.show', compact('auditLog', 'isValid'));
    }

    /**
     * Export audit logs to CSV
     */
    public function exportCsv(Request $request)
    {
        $this->authorize('isAdmin');

        $query = $this->buildQuery($request);
        $auditLogs = $query->get();

        return Excel::download(
            new AuditLogsExport($auditLogs),
            'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * Export audit logs to PDF
     */
    public function exportPdf(Request $request)
    {
        $this->authorize('isAdmin');

        $query = $this->buildQuery($request);
        $auditLogs = $query->with('user')->get();

        $pdf = Pdf::loadView('admin.audit.pdf', compact('auditLogs'));

        return $pdf->download('audit_logs_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    /**
     * Get statistics for dashboard
     */
    public function statistics()
    {
        $this->authorize('isAdmin');

        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisYear = Carbon::now()->startOfYear();

        return response()->json([
            'total_logs' => AuditLog::count(),
            'today_logs' => AuditLog::whereDate('created_at', $today)->count(),
            'month_logs' => AuditLog::whereBetween('created_at', [
                $thisMonth,
                $thisMonth->copy()->endOfMonth(),
            ])->count(),
            'critical_events' => AuditLog::whereIn('event', [
                'role_changed',
                '2fa_disabled',
                'password_changed',
                'force_deleted',
            ])->count(),
            'archived_logs' => AuditLog::archived()->count(),
            'events_by_type' => AuditLog::select('event')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('event')
                ->pluck('count', 'event'),
        ]);
    }

    /**
     * Get recent critical events
     */
    public function recentCritical()
    {
        $this->authorize('isAdmin');

        $criticalEvents = AuditLog::with('user')
            ->whereIn('event', [
                'role_changed',
                '2fa_disabled',
                'password_changed',
                'force_deleted',
                'backup_failed',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($criticalEvents);
    }

    /**
     * Build query based on filters
     */
    protected function buildQuery(Request $request)
    {
        $query = AuditLog::query();

        if ($request->filled('event')) {
            $query->byEvent($request->event);
        }

        if ($request->filled('model_type')) {
            $query->byModel($request->model_type);
        }

        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->dateRange(
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_to)->endOfDay()
            );
        }

        if (!$request->boolean('show_archived')) {
            $query->active();
        }

        return $query;
    }
}
