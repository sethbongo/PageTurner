@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Audit Logs</h1>
        <p class="text-gray-600 mt-2">View and manage system audit logs for compliance and security</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium">Total Logs</div>
            <div class="mt-2 text-3xl font-bold text-gray-900" id="total-logs">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium">Today's Logs</div>
            <div class="mt-2 text-3xl font-bold text-gray-900" id="today-logs">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium">Critical Events</div>
            <div class="mt-2 text-3xl font-bold text-red-600" id="critical-logs">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-gray-500 text-sm font-medium">Archived Logs</div>
            <div class="mt-2 text-3xl font-bold text-gray-900" id="archived-logs">-</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Filters</h2>
            <form method="GET" action="{{ route('audit.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Event Type -->
                    <div>
                        <label for="event" class="block text-sm font-medium text-gray-700 mb-1">Event Type</label>
                        <select name="event" id="event" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">All Events</option>
                            @foreach($eventTypes as $event)
                                <option value="{{ $event }}" {{ request('event') === $event ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $event)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Model Type -->
                    <div>
                        <label for="model_type" class="block text-sm font-medium text-gray-700 mb-1">Model Type</label>
                        <select name="model_type" id="model_type" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">All Models</option>
                            @foreach($modelTypes as $type)
                                @if($type)
                                    <option value="{{ $type }}" {{ request('model_type') === $type ? 'selected' : '' }}>
                                        {{ class_basename($type) }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <!-- User -->
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                        <select name="user_id" id="user_id" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">All Users</option>
                            @foreach($users as $id => $email)
                                <option value="{{ $id }}" {{ request('user_id') == $id ? 'selected' : '' }}>
                                    {{ $email }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" 
                            class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" 
                            class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>

                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search (IP, URL)</label>
                        <input type="text" name="search" id="search" placeholder="Search..." value="{{ request('search') }}" 
                            class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                </div>

                <!-- Checkboxes -->
                <div class="flex items-center space-x-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="critical_only" value="1" {{ request('critical_only') ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">Critical Events Only</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="show_archived" value="1" {{ request('show_archived') ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm text-gray-700">Show Archived</span>
                    </label>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-4 pt-4">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                        Filter
                    </button>
                    <a href="{{ route('audit.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Reset
                    </a>
                    <a href="{{ route('audit.export-csv', request()->query()) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        📥 Export CSV
                    </a>
                    <a href="{{ route('audit.export-pdf', request()->query()) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        📄 Export PDF
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($auditLogs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->created_at->format('M d, Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->user?->email ?? 'System' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                    @if(in_array($log->event, ['role_changed', 'force_deleted', '2fa_disabled']))
                                        bg-red-100 text-red-800
                                    @elseif(in_array($log->event, ['created', 'deleted']))
                                        bg-yellow-100 text-yellow-800
                                    @else
                                        bg-green-100 text-green-800
                                    @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $log->event)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $log->auditable_type ? class_basename($log->auditable_type) . ' #' . $log->auditable_id : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $log->metadata['ip_address'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($log->archived)
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                        Archived
                                    </span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                        Active
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('audit.show', $log) }}" class="text-blue-600 hover:text-blue-900">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No audit logs found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            {{ $auditLogs->links() }}
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('{{ route('audit.statistics') }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-logs').textContent = data.total_logs.toLocaleString();
            document.getElementById('today-logs').textContent = data.today_logs.toLocaleString();
            document.getElementById('critical-logs').textContent = data.critical_events.toLocaleString();
            document.getElementById('archived-logs').textContent = data.archived_logs.toLocaleString();
        });
});
</script>
@endsection
