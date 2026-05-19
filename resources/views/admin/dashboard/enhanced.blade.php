@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-gray-50">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
                    <form action="{{ route('admin.dashboard.clear-cache') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                            Refresh Data
                        </button>
                    </form>
                </div>

                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Import/Export Status Widget -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Import/Export Status</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div class="grid grid-cols-3 gap-4 mb-6">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-blue-600">
                                        {{ $importExportStatus['total_operations'] }}
                                    </p>
                                    <p class="text-gray-600">Total Operations</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-green-600">{{ $importExportStatus['success_rate'] }}%
                                    </p>
                                    <p class="text-gray-600">Success Rate</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-red-600">{{ $importExportStatus['failure_rate'] }}%
                                    </p>
                                    <p class="text-gray-600">Failure Rate</p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h3 class="font-semibold text-gray-700 mb-2">Queue Status</h3>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span>Pending:</span>
                                        <span class="font-semibold">{{ $importExportStatus['pending_count'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Processing:</span>
                                        <span class="font-semibold">{{ $importExportStatus['processing_count'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <h3 class="font-semibold text-gray-700 mb-2">Recent Operations</h3>
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    @forelse($importExportStatus['recent_operations'] as $op)
                                        <div class="text-sm p-2 bg-gray-50 rounded">
                                            <p class="font-medium">{{ $op->module }} - {{ strtoupper($op->type) }}</p>
                                            <p class="text-gray-600">{{ $op->user?->name ?? 'System' }}</p>
                                            <p class="text-xs text-gray-500">{{ $op->created_at->diffForHumans() }}</p>
                                        </div>
                                    @empty
                                        <p class="text-gray-500 text-sm">No recent operations</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backup Status Widget -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Backup Status</h2>
                        </div>
                        <div class="px-6 py-4">
                            @if($backupStatus['latest_backup'])
                                <div class="mb-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-gray-700">Latest Backup:</span>
                                        <span
                                            class="px-3 py-1 rounded text-sm font-semibold {{ $backupStatus['is_healthy'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $backupStatus['is_healthy'] ? 'Healthy' : 'Stale' }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ $backupStatus['latest_backup']->backup_name }}</p>
                                    <p class="text-sm text-gray-600">Size: {{ $backupStatus['storage_used'] }}</p>
                                    <p class="text-sm text-gray-600">
                                        {{ $backupStatus['latest_backup']->completed_at?->format('Y-m-d H:i:s') ?? 'Pending' }}
                                    </p>
                                </div>

                                <div class="mb-4">
                                    <h3 class="font-semibold text-gray-700 mb-2">Health Summary</h3>
                                    <div class="space-y-1">
                                        @foreach($backupStatus['health_summary'] as $status => $count)
                                            <div class="flex justify-between text-sm">
                                                <span class="capitalize">{{ $status }}:</span>
                                                <span class="font-semibold">{{ $count }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <p class="text-gray-500">No backups available</p>
                            @endif

                            <div class="mt-4">
                                <h3 class="font-semibold text-gray-700 mb-2">Recent Backups</h3>
                                <div class="space-y-2 max-h-48 overflow-y-auto">
                                    @forelse($backupStatus['backup_history'] as $backup)
                                        <div class="text-sm p-2 bg-gray-50 rounded">
                                            <p class="font-medium">{{ $backup->backup_name }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $backup->completed_at?->diffForHumans() ?? 'Pending' }}
                                            </p>
                                        </div>
                                    @empty
                                        <p class="text-gray-500 text-sm">No backup history</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <a href="{{ route('admin.backups.index') }}"
                                    class="w-full block text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium text-sm">
                                    Manage Backups
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Audit Log Summary Widget -->
                <div class="bg-white rounded-lg shadow mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Audit Log Summary</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-blue-600">{{ $auditLogSummary['total_logs'] }}</p>
                                <p class="text-gray-600">Total Logs</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl font-bold text-purple-600">{{ $auditLogSummary['events_today'] }}</p>
                                <p class="text-gray-600">Events Today</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl font-bold text-orange-600">{{ $auditLogSummary['active_users_24h'] }}</p>
                                <p class="text-gray-600">Active Users (24h)</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl font-bold text-red-600">{{ count($auditLogSummary['critical_events']) }}
                                </p>
                                <p class="text-gray-600">Critical Events</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <h3 class="font-semibold text-gray-700 mb-2">Recent Critical Events</h3>
                                <div class="space-y-2 max-h-64 overflow-y-auto">
                                    @forelse($auditLogSummary['critical_events'] as $event)
                                        <div class="text-sm p-2 bg-red-50 rounded border-l-4 border-red-500">
                                            <p class="font-medium">{{ $event->event }}</p>
                                            <p class="text-gray-600 text-xs">{{ $event->user?->name ?? 'System' }}</p>
                                            <p class="text-xs text-gray-500">{{ $event->created_at->diffForHumans() }}</p>
                                        </div>
                                    @empty
                                        <p class="text-gray-500 text-sm">No critical events</p>
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <h3 class="font-semibold text-gray-700 mb-2">Event Summary (Last 24h)</h3>
                                <div class="space-y-1">
                                    @forelse($auditLogSummary['alerts_summary'] as $event => $count)
                                        <div class="flex justify-between text-sm">
                                            <span class="capitalize">{{ $event }}:</span>
                                            <span class="font-semibold">{{ $count }}</span>
                                        </div>
                                    @empty
                                        <p class="text-gray-500 text-sm">No events recorded</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Usage Statistics Widget -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">API Usage Statistics</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div class="mb-6">
                                <p class="text-sm text-gray-600 mb-2">Total Requests Today: <span
                                        class="font-bold text-lg">{{ $apiUsageStats['total_requests_today'] }}</span></p>
                                <p class="text-sm text-gray-600">Rate Limit Hits (24h): <span
                                        class="font-bold text-lg text-red-600">{{ $apiUsageStats['rate_limit_hits_24h'] }}</span>
                                </p>
                            </div>

                            <h3 class="font-semibold text-gray-700 mb-2">Requests by Endpoint</h3>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                @forelse($apiUsageStats['requests_by_endpoint'] as $endpoint => $count)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">{{ $endpoint }}</span>
                                        <span
                                            class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">{{ $count }}</span>
                                    </div>
                                @empty
                                    <p class="text-gray-500 text-sm">No API requests</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- System Health Widget -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">System Health</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Database Size</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ $systemHealth['database_size_mb'] }} MB
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Storage Used</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ $systemHealth['storage_used_mb'] }} MB
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 mb-1">Failed Jobs</p>
                                    <p
                                        class="text-2xl font-bold {{ $systemHealth['failed_jobs'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $systemHealth['failed_jobs'] }}
                                    </p>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-700 mb-2">Queue Lengths</h3>
                                    <div class="space-y-1">
                                        @foreach($systemHealth['queue_lengths'] as $queue => $length)
                                            <div class="flex justify-between text-sm">
                                                <span class="capitalize">{{ str_replace('_', ' ', $queue) }}:</span>
                                                <span class="font-semibold">{{ $length }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top API Users -->
                @if($apiUsageStats['top_users']->isNotEmpty())
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">Top API Users (24h)</h2>
                        </div>
                        <div class="px-6 py-4">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2 px-4 text-gray-600">User</th>
                                        <th class="text-right py-2 px-4 text-gray-600">Requests</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($apiUsageStats['top_users'] as $user)
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="py-3 px-4">{{ $user->user?->name ?? 'Unknown' }}</td>
                                            <td class="text-right py-3 px-4 font-semibold">{{ $user->total }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection