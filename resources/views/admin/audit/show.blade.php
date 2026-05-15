@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="mb-8">
        <a href="{{ route('audit.index') }}" class="text-blue-600 hover:text-blue-900 text-sm font-medium mb-4 inline-block">
            &larr; Back to Audit Logs
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Audit Log Details</h1>
        <p class="text-gray-600 mt-2">{{ $auditLog->created_at->format('M d, Y H:i:s') }}</p>
    </div>

    <!-- Integrity Status -->
    <div class="mb-6 p-4 rounded-lg {{ $isValid ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                @if($isValid)
                    <svg class="h-5 w-5 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                @else
                    <svg class="h-5 w-5 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                @endif
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium {{ $isValid ? 'text-green-800' : 'text-red-800' }}">
                    @if($isValid)
                        ✓ Log integrity verified - Checksum matches
                    @else
                        ✗ Log integrity warning - Checksum mismatch (possible tampering)
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Basic Information</h2>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Event</label>
                            <p class="text-sm text-gray-900 mt-1">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                    @if(in_array($auditLog->event, ['role_changed', 'force_deleted', '2fa_disabled']))
                                        bg-red-100 text-red-800
                                    @elseif(in_array($auditLog->event, ['created', 'deleted']))
                                        bg-yellow-100 text-yellow-800
                                    @else
                                        bg-green-100 text-green-800
                                    @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $auditLog->event)) }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">User</label>
                            <p class="text-sm text-gray-900 mt-1">{{ $auditLog->user?->email ?? 'System' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Model</label>
                            <p class="text-sm text-gray-900 mt-1">
                                {{ $auditLog->auditable_type ? class_basename($auditLog->auditable_type) : '-' }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Model ID</label>
                            <p class="text-sm text-gray-900 mt-1">{{ $auditLog->auditable_id ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Request Information -->
            <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Request Information</h2>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">IP Address</label>
                        <p class="text-sm text-gray-900 mt-1 font-mono">{{ $auditLog->metadata['ip_address'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Method</label>
                        <p class="text-sm text-gray-900 mt-1 font-mono">{{ $auditLog->metadata['method'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">URL</label>
                        <p class="text-sm text-gray-900 mt-1 font-mono break-all">{{ $auditLog->metadata['url'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">User Agent</label>
                        <p class="text-sm text-gray-900 mt-1 font-mono text-xs break-all">{{ $auditLog->metadata['user_agent'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Changes Comparison -->
            @if($auditLog->old_values || $auditLog->new_values)
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Changes</h2>
                    </div>
                    <div class="px-6 py-4">
                        @php
                            $allKeys = collect($auditLog->old_values)->keys()->merge(collect($auditLog->new_values)->keys())->unique();
                        @endphp
                        @foreach($allKeys as $key)
                            <div class="mb-6 last:mb-0">
                                <h3 class="text-sm font-semibold text-gray-900 mb-2">{{ ucfirst(str_replace('_', ' ', $key)) }}</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Old Value</label>
                                        <div class="bg-red-50 border border-red-200 rounded p-3">
                                            <code class="text-xs text-red-900 break-all">
                                                @if(isset($auditLog->old_values[$key]))
                                                    {{ is_array($auditLog->old_values[$key]) ? json_encode($auditLog->old_values[$key], JSON_PRETTY_PRINT) : $auditLog->old_values[$key] }}
                                                @else
                                                    <em class="text-gray-500">Not set</em>
                                                @endif
                                            </code>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">New Value</label>
                                        <div class="bg-green-50 border border-green-200 rounded p-3">
                                            <code class="text-xs text-green-900 break-all">
                                                @if(isset($auditLog->new_values[$key]))
                                                    {{ is_array($auditLog->new_values[$key]) ? json_encode($auditLog->new_values[$key], JSON_PRETTY_PRINT) : $auditLog->new_values[$key] }}
                                                @else
                                                    <em class="text-gray-500">Not set</em>
                                                @endif
                                            </code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Checksum -->
            <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Security</h2>
                </div>
                <div class="px-6 py-4">
                    <label class="block text-sm font-medium text-gray-700">Checksum (SHA-256)</label>
                    <p class="text-xs text-gray-900 mt-2 font-mono break-all bg-gray-50 p-2 rounded">{{ $auditLog->checksum }}</p>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Timeline</h2>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Created</label>
                        <p class="text-sm text-gray-900 mt-1">{{ $auditLog->created_at->format('M d, Y H:i:s') }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $auditLog->created_at->diffForHumans() }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <p class="text-sm text-gray-900 mt-1">
                            @if($auditLog->archived)
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                    Archived
                                </span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    Active
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
