<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Import & Export Center') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <x-flash-messages />

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Book Import</h3>
                        <p class="text-sm text-gray-600 mb-4">Upload XLSX/CSV files with the required headers.</p>
                        <div class="flex items-center gap-3 mb-4">
                            <a href="{{ route('admin.imports.books.template') }}"
                                class="text-sm text-indigo-600 hover:text-indigo-800">Download Template</a>
                            <span class="text-xs text-gray-400">Headers: ISBN, Title, Author, Price, Stock, Category,
                                Description</span>
                        </div>
                        <form action="{{ route('admin.imports.books') }}" method="POST" enctype="multipart/form-data"
                            class="space-y-4">
                            @csrf
                            <div>
                                <input type="file" name="file" class="block w-full text-sm text-gray-700" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Duplicate Handling</label>
                                <select name="duplicate_mode" class="mt-1 block w-full rounded-md border-gray-300"
                                    required>
                                    <option value="skip">Skip duplicates</option>
                                    <option value="update">Update existing</option>
                                </select>
                            </div>
                            <button type="submit"
                                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Import
                                Books</button>
                        </form>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Book Export</h3>
                        <form action="{{ route('admin.exports.books') }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Category</label>
                                    <select name="category_id" class="mt-1 block w-full rounded-md border-gray-300">
                                        <option value="">All Categories</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Stock Status</label>
                                    <select name="stock_status" class="mt-1 block w-full rounded-md border-gray-300">
                                        <option value="">Any</option>
                                        <option value="in_stock">In Stock</option>
                                        <option value="out_of_stock">Out of Stock</option>
                                        <option value="low_stock">Low Stock (&le; 10)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Price Min</label>
                                    <input type="number" step="0.01" name="price_min"
                                        class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Price Max</label>
                                    <input type="number" step="0.01" name="price_max"
                                        class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date From</label>
                                    <input type="date" name="date_from"
                                        class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date To</label>
                                    <input type="date" name="date_to"
                                        class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-gray-700 mb-2">Columns</p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                                    @foreach(['isbn' => 'ISBN', 'title' => 'Title', 'author' => 'Author', 'price' => 'Price', 'stock' => 'Stock', 'category' => 'Category', 'description' => 'Description', 'created_at' => 'Created At'] as $key => $label)
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" name="columns[]" value="{{ $key }}" checked>
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Format</label>
                                <select name="format" class="mt-1 block w-full rounded-md border-gray-300" required>
                                    <option value="xlsx">XLSX</option>
                                    <option value="csv">CSV</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>
                            <button type="submit"
                                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Export
                                Books</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Export</h3>
                        <form action="{{ route('admin.exports.orders') }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status</label>
                                    <select name="status" class="mt-1 block w-full rounded-md border-gray-300">
                                        <option value="">Any</option>
                                        @foreach(['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Failed'] as $status)
                                            <option value="{{ $status }}">{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Customer</label>
                                    <select name="customer_id" class="mt-1 block w-full rounded-md border-gray-300">
                                        <option value="">All Customers</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date From</label>
                                    <input type="date" name="date_from"
                                        class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date To</label>
                                    <input type="date" name="date_to"
                                        class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Format</label>
                                <select name="format" class="mt-1 block w-full rounded-md border-gray-300" required>
                                    <option value="xlsx">XLSX</option>
                                    <option value="csv">CSV</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>
                            <button type="submit"
                                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Export
                                Orders</button>
                        </form>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Reports</h3>
                        <form action="{{ route('admin.exports.financial') }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date From</label>
                                    <input type="date" name="date_from"
                                        class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date To</label>
                                    <input type="date" name="date_to"
                                        class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Format</label>
                                <select name="format" class="mt-1 block w-full rounded-md border-gray-300" required>
                                    <option value="xlsx">XLSX</option>
                                    <option value="csv">CSV</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>
                            <button type="submit"
                                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Export Financial
                                Report</button>
                            <p class="text-xs text-gray-500">Tax rate is currently
                                {{ number_format(config('reports.tax_rate') * 100, 2) }}%.</p>
                        </form>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">User Import</h3>
                        <p class="text-sm text-gray-600 mb-4">Bulk create users with role assignment.</p>
                        <div class="flex items-center gap-3 mb-4">
                            <a href="{{ route('admin.imports.users.template') }}"
                                class="text-sm text-indigo-600 hover:text-indigo-800">Download Template</a>
                        </div>
                        <form action="{{ route('admin.imports.users') }}" method="POST" enctype="multipart/form-data"
                            class="space-y-4">
                            @csrf
                            <div>
                                <input type="file" name="file" class="block w-full text-sm text-gray-700" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Default Role</label>
                                <select name="default_role" class="mt-1 block w-full rounded-md border-gray-300"
                                    required>
                                    <option value="customer">Customer</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <button type="submit"
                                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Import
                                Users</button>
                        </form>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">User Export</h3>
                        <form action="{{ route('admin.exports.users') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="redact" value="1">
                                    <span class="text-sm text-gray-700">Redact PII (GDPR)</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Format</label>
                                <select name="format" class="mt-1 block w-full rounded-md border-gray-300" required>
                                    <option value="xlsx">XLSX</option>
                                    <option value="csv">CSV</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>
                            <button type="submit"
                                class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Export
                                Users</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Import/Export Logs</h3>
                    @if($logs->isEmpty())
                        <p class="text-sm text-gray-500">No logs yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-left">ID</th>
                                        <th class="px-4 py-2 text-left">Module</th>
                                        <th class="px-4 py-2 text-left">Type</th>
                                        <th class="px-4 py-2 text-left">Status</th>
                                        <th class="px-4 py-2 text-left">Rows</th>
                                        <th class="px-4 py-2 text-left">Started</th>
                                        <th class="px-4 py-2 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($logs as $log)
                                        <tr>
                                            <td class="px-4 py-2">#{{ $log->id }}</td>
                                            <td class="px-4 py-2 capitalize">{{ $log->module }}</td>
                                            <td class="px-4 py-2 capitalize">{{ $log->type }}</td>
                                            <td class="px-4 py-2">{{ $log->status }}</td>
                                            <td class="px-4 py-2">{{ $log->processed_rows }}/{{ $log->total_rows ?? '-' }}</td>
                                            <td class="px-4 py-2">{{ optional($log->started_at)->format('M d, Y H:i') }}</td>
                                            <td class="px-4 py-2 space-x-2">
                                                @if($log->stored_path)
                                                    <a href="{{ route('admin.exports.download', $log) }}"
                                                        class="text-indigo-600 hover:text-indigo-800">Download</a>
                                                @endif
                                                @if($log->type === 'import' && $log->failed_rows > 0)
                                                    <a href="{{ route('admin.imports.failures', $log) }}"
                                                        class="text-amber-600 hover:text-amber-800">Failures</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>