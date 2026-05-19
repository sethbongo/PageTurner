@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Data Portability & Privacy</h1>
                <p class="text-gray-600 mt-2">Manage your data and export your personal information in accordance with GDPR
                    regulations.</p>
            </div>

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Export Options -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Personal Data Export -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 bg-blue-50 border-b border-blue-200">
                        <h2 class="text-lg font-semibold text-gray-900">Export Personal Data</h2>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-gray-600 mb-4">Download your personal information in GDPR-compliant JSON format,
                            including your account details, preferences, and activity.</p>
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-2">Includes:</p>
                            <ul class="text-sm text-gray-600 space-y-1 ml-4">
                                <li>✓ Account information</li>
                                <li>✓ Contact details</li>
                                <li>✓ Purchase history</li>
                                <li>✓ Reviews and ratings</li>
                                <li>✓ Account preferences</li>
                            </ul>
                        </div>
                        <form action="{{ route('user.data-portability.export-personal-json') }}" method="POST"
                            class="inline">
                            @csrf
                            <button type="submit"
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                                Download as JSON
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Order History Export -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 bg-green-50 border-b border-green-200">
                        <h2 class="text-lg font-semibold text-gray-900">Order History</h2>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-gray-600 mb-4">Download a complete record of all your orders with detailed
                            information about items purchased.</p>
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-2">Your Statistics:</p>
                            <ul class="text-sm text-gray-600 space-y-1 ml-4">
                                <li>📦 Total Orders: <strong>{{ $orderSummary['total_orders'] }}</strong></li>
                                <li>💰 Total Spent: <strong>${{ number_format($orderSummary['total_spent'], 2) }}</strong>
                                </li>
                                <li>📊 Avg Order Value:
                                    <strong>${{ number_format($orderSummary['average_order_value'], 2) }}</strong></li>
                            </ul>
                        </div>
                        <div class="space-y-2">
                            <form action="{{ route('user.data-portability.export-orders-excel') }}" method="POST"
                                class="inline w-full">
                                @csrf
                                <button type="submit"
                                    class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                                    Download as Excel
                                </button>
                            </form>
                            <form action="{{ route('user.data-portability.export-orders-pdf') }}" method="POST"
                                class="inline w-full">
                                @csrf
                                <button type="submit"
                                    class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                                    Download as PDF
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Reading History Export -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 bg-purple-50 border-b border-purple-200">
                        <h2 class="text-lg font-semibold text-gray-900">Reading History</h2>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-gray-600 mb-4">Export your browsing and purchase history with genre preferences and
                            reading statistics.</p>
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-2">Includes:</p>
                            <ul class="text-sm text-gray-600 space-y-1 ml-4">
                                <li>✓ All books purchased</li>
                                <li>✓ Genre preferences</li>
                                <li>✓ Reading timeline</li>
                                <li>✓ Statistics & insights</li>
                            </ul>
                        </div>
                        <div class="space-y-2">
                            <form action="{{ route('user.data-portability.export-reading-json') }}" method="POST"
                                class="inline w-full">
                                @csrf
                                <button type="submit"
                                    class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-medium">
                                    Download as JSON
                                </button>
                            </form>
                            <form action="{{ route('user.data-portability.export-reading-pdf') }}" method="POST"
                                class="inline w-full">
                                @csrf
                                <button type="submit"
                                    class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                                    Download as PDF
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Data Deletion Request -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 bg-red-50 border-b border-red-200">
                        <h2 class="text-lg font-semibold text-gray-900">Request Data Deletion</h2>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-gray-600 mb-4">Request the deletion of your personal data. We will process your
                            request in accordance with GDPR.</p>
                        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                            <p class="text-sm text-yellow-800">⚠️ This action is irreversible. We will delete all your
                                personal data within 30 days.</p>
                        </div>
                        <button onclick="openDeletionModal()"
                            class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                            Request Deletion
                        </button>
                    </div>
                </div>
            </div>

            <!-- Info Section -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Data Portability Rights</h3>
                <p class="text-gray-700 mb-3">Under the General Data Protection Regulation (GDPR), you have the right to:
                </p>
                <ul class="space-y-2 text-gray-700 ml-4">
                    <li>✓ Access your personal data</li>
                    <li>✓ Receive your data in a portable format</li>
                    <li>✓ Transfer your data to another service</li>
                    <li>✓ Request deletion of your data</li>
                    <li>✓ Understand what data we hold about you</li>
                </ul>
                <p class="text-sm text-gray-600 mt-4">For more information, please refer to our <a href="#"
                        class="text-blue-600 hover:underline">Privacy Policy</a>.</p>
            </div>
        </div>
    </div>

    <!-- Data Deletion Modal -->
    <div id="deletionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Confirm Data Deletion</h3>
            </div>
            <div class="px-6 py-4">
                <p class="text-gray-700 mb-4">This will permanently delete all your personal data. This action cannot be
                    undone.</p>

                <form action="{{ route('user.data-portability.request-deletion') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Why are you deleting your
                            account? (optional)</label>
                        <textarea name="reason" id="reason" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            maxlength="500"></textarea>
                    </div>

                    <div class="mb-6 p-3 bg-red-50 border border-red-200 rounded">
                        <label class="flex items-center">
                            <input type="checkbox" name="confirm" value="on" class="rounded border-gray-300" required>
                            <span class="ml-2 text-sm text-gray-700">I understand this action is irreversible and my data
                                will be permanently deleted.</span>
                        </label>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" onclick="closeDeletionModal()"
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition font-medium">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                            Delete My Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openDeletionModal() {
            document.getElementById('deletionModal').classList.remove('hidden');
        }

        function closeDeletionModal() {
            document.getElementById('deletionModal').classList.add('hidden');
        }

        document.getElementById('deletionModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeDeletionModal();
            }
        });
    </script>
@endsection