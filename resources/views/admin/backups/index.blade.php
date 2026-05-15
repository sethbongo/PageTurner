<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Backup Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Alert Messages -->
            <x-flash-messages />

            <!-- Backup Status Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Backup Status Card -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500 mb-4">Backup Status</h3>
                        <div class="flex items-center">
                            @if ($backupStatus['status'] === 'healthy')
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    ✓ Healthy
                                </span>
                            @elseif ($backupStatus['status'] === 'warning')
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                    ⚠ Warning
                                </span>
                            @elseif ($backupStatus['status'] === 'critical')
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    ✗ Critical
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                    ⚪ Unknown
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mt-3">{{ $backupStatus['message'] }}</p>
                        @if ($backupStatus['lastBackup'])
                            <p class="text-xs text-gray-500 mt-2">Last: {{ $backupStatus['lastBackup'] }}</p>
                        @endif
                    </div>
                </div>

                <!-- Storage Usage Card -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500 mb-4">Storage Usage</h3>
                        <p class="text-2xl font-bold text-gray-900">{{ $diskUsage['readable_total'] }}</p>
                        <p class="text-xs text-gray-500 mt-2">{{ $diskUsage['file_count'] }} backup file(s)</p>
                    </div>
                </div>

                <!-- Actions Card -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500 mb-4">Quick Actions</h3>
                        <div class="space-y-2">
                            <button onclick="triggerBackup()"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm transition duration-200">
                                🔄 Trigger Backup Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup List Section -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Backup Files</h3>
                    <p class="text-sm text-gray-600 mt-1">Manage your automated backups</p>
                </div>

                @if (count($backups) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Filename</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Size</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Date</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($backups as $backup)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $backup['filename'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $backup['readable_size'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $backup['readable_date'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('admin.backups.download', $backup['filename']) }}"
                                                class="text-blue-600 hover:text-blue-900 font-medium mr-3">
                                                ⬇ Download
                                            </a>
                                            <button onclick="deleteBackup('{{ $backup['filename'] }}')"
                                                class="text-red-600 hover:text-red-900 font-medium">
                                                🗑 Delete
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-6 text-center">
                        <p class="text-gray-500">No backups found. <button onclick="triggerBackup()"
                                class="text-blue-600 hover:underline">Create one now</button></p>
                    </div>
                @endif
            </div>

            <!-- Backup Schedule Information -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-4">📅 Backup Schedule</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-blue-800 mb-2">Automated Tasks</h4>
                        <ul class="space-y-2 text-sm text-blue-700">
                            <li>✓ <strong>Daily at 02:00 AM:</strong> Full database and files backup</li>
                            <li>✓ <strong>Daily at 03:00 AM:</strong> Cleanup old backups (retention policy)</li>
                            <li>✓ <strong>Hourly:</strong> Cancel pending orders > 24 hours</li>
                            <li>✓ <strong>Daily:</strong> Clear expired sessions</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-blue-800 mb-2">Retention Policy</h4>
                        <ul class="space-y-2 text-sm text-blue-700">
                            <li>📌 <strong>Daily backups:</strong> Keep 7 days</li>
                            <li>📌 <strong>Weekly backups:</strong> Keep 4 weeks</li>
                            <li>📌 <strong>Monthly backups:</strong> Keep 12 months</li>
                            <li>📌 <strong>Compression:</strong> GZIP format</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Server Cron Configuration -->
            <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">🖥️ Server Cron Configuration</h3>
                <p class="text-sm text-gray-600 mb-3">Add this line to your server's crontab to enable automated
                    scheduling:</p>
                <div class="bg-gray-900 text-gray-100 p-4 rounded font-mono text-sm overflow-x-auto">
                    * * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1
                </div>
                <p class="text-xs text-gray-500 mt-2">This command should be executed every minute by the system cron.
                </p>
            </div>
        </div>
    </div>

    <script>
        function triggerBackup() {
            if (confirm('Are you sure you want to trigger a backup now? This may take several minutes.')) {
                fetch('{{ route("admin.backups.trigger") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('✓ ' + data.message);
                            location.reload();
                        } else {
                            alert('✗ ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('✗ Error triggering backup: ' + error.message);
                    });
            }
        }

        function deleteBackup(filename) {
            if (confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
                fetch('{{ route("admin.backups.delete", ":filename") }}'.replace(':filename', filename), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('✓ ' + data.message);
                            location.reload();
                        } else {
                            alert('✗ ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('✗ Error deleting backup: ' + error.message);
                    });
            }
        }
    </script>
</x-admin-layout>