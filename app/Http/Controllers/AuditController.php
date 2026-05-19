<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Allowed event types for filter dropdown
        $eventTypes = collect(['created', 'updated', 'deleted']);

        // Get users for filter dropdown
        $users = User::orderBy('email')->pluck('email', 'id');

        return view('admin.audit.index', compact(
            'auditLogs',
            'eventTypes',
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
