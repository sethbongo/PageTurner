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

            <!-- Header with Button -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Backup Management</h1>
                <button onclick="triggerBackup()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    Trigger Backup Now
                </button>
            </div>

            <!-- Backup Status Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Backup Status Card -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-900">Status</h3>
                    </div>
                    <div class="px-6 py-4">
                        @if ($backupStatus['status'] === 'healthy')
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                Healthy
                            </span>
                        @elseif ($backupStatus['status'] === 'warning')
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                Warning
                            </span>
                        @elseif ($backupStatus['status'] === 'critical')
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                Critical
                            </span>
                        @else
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                Unknown
                            </span>
                        @endif
                        <p class="text-sm text-gray-600 mt-3">{{ $backupStatus['message'] }}</p>
                        @if ($backupStatus['lastBackup'])
                            <p class="text-xs text-gray-500 mt-2">Last: {{ $backupStatus['lastBackup'] }}</p>
                        @endif
                    </div>
                </div>

                <!-- Storage Usage Card -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-900">Storage Usage</h3>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-2xl font-bold text-gray-900">{{ $diskUsage['readable_total'] }}</p>
                        <p class="text-sm text-gray-600 mt-2">{{ $diskUsage['file_count'] }} backup(s)</p>
                    </div>
                </div>

                <!-- Backup Count Card -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-900">Files</h3>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-2xl font-bold text-blue-600">{{ count($backups) }}</p>
                        <p class="text-sm text-gray-600 mt-2">Available backups</p>
                    </div>
                </div>
            </div>

            <!-- Backup List Section -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Backup Files</h3>
                </div>

                @if (count($backups) > 0)
                    <div class="divide-y divide-gray-200">
                        @foreach ($backups as $backup)
                            <div class="px-6 py-4 hover:bg-gray-50 transition">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900">{{ $backup['filename'] }}</p>
                                        <div class="flex gap-4 mt-1 text-sm text-gray-600">
                                            <span>{{ $backup['readable_size'] }}</span>
                                            <span>{{ $backup['readable_date'] }}</span>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 ml-4">
                                        <a href="{{ route('admin.backups.download', $backup['filename']) }}"
                                            class="px-3 py-1 text-sm text-blue-600 hover:text-blue-900 font-medium transition">
                                            Download
                                        </a>
                                        <button onclick="deleteBackup('{{ $backup['filename'] }}')"
                                            class="px-3 py-1 text-sm text-red-600 hover:text-red-900 font-medium transition">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <p class="text-gray-500">No backups found yet</p>
                        <button onclick="triggerBackup()" class="mt-3 text-blue-600 hover:text-blue-900 font-medium">
                            Create your first backup
                        </button>
                    </div>
                @endif
            </div>

            <!-- Backup Schedule Information -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-900">Schedule & Retention</h3>
                </div>
                <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3">Automated Backups</h4>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li> <strong>Daily 02:00 AM:</strong> Full backup</li>
                            <li> <strong>Daily 03:00 AM:</strong> Cleanup old backups</li>
                            <li> <strong>Hourly:</strong> Order cleanup</li>
                            <li> <strong>Daily:</strong> Session cleanup</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3">Retention Policy</h4>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li> Daily: 7 days</li>
                            <li>Weekly: 4 weeks</li>
                            <li> Monthly: 12 months</li>
                            <li> Format: GZIP compression</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Server Configuration -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-900">Server Configuration</h3>
                </div>
                <div class="px-6 py-4">
                    <p class="text-sm text-gray-600 mb-3">Add this line to your server's crontab:</p>
                    <div class="bg-gray-900 text-gray-100 p-4 rounded font-mono text-xs overflow-x-auto">
                        * * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1
                    </div>
                    <p class="text-xs text-gray-500 mt-3">Run every minute for automatic scheduling</p>
                </div>
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
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                })
                    .then(response => {
                        const contentType = response.headers.get('content-type') || '';
                        if (!contentType.includes('application/json')) {
                            return response.text().then(() => {
                                throw new Error('Session expired or unauthorized. Please refresh and try again.');
                            });
                        }

                        return response.json();
                    })
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