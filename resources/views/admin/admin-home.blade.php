<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>
    
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            <x-flash-messages/>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Books -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500">Total Books</h3>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalBooks }}</p>
                    </div>
                </div>

                <!-- Total Categories -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500">Categories</h3>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalCategories }}</p>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500">Total Orders</h3>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalOrders }}</p>
                    </div>
                </div>

                <!-- Total Customers -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500">Customers</h3>
                        <p class="text-2xl font-bold text-gray-900">{{ $totalUsers }}</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-8">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button onclick="openAddBookModal()" class="flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors text-left">
                            <span class="text-gray-700 font-medium">+ Add New Book</span>
                        </button>

                        <button onclick="openAddCategoryModal()" class="flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors text-left">
                            <span class="text-gray-700 font-medium">+ Add New Category</span>
                        </button>

                        <a href="{{ route('admin.manage_books') }}" class="flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors">
                            <span class="text-gray-700 font-medium">Manage Books</span>
                        </a>

                        <a href="{{ route('admin.manage_categories') }}" class="flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors">
                            <span class="text-gray-700 font-medium">Manage Categories</span>
                        </a>

                        <a href="{{ route('admin.customer_orders') }}" class="flex items-center p-4 border border-gray-300 hover:border-gray-400 rounded-lg transition-colors">
                            <span class="text-gray-700 font-medium">Customer Orders</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals -->
    @include('admin.add-book-modal')
    @include('admin.add-category-modal')
</x-admin-layout>