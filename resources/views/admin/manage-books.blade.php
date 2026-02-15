<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Books') }}
        </h2>
    </x-slot>
    
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            <x-flash-messages/>

            <!-- Books Grid -->
            @if($books->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6 text-center">
                        <p class="text-gray-500">No books found. Add your first book from the dashboard.</p>
                    </div>
                </div>
            @else

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($books as $book)
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            <!-- Book Cover -->
                            <x-shared-book-card :book="$book"/>
                                
                                <p class="text-sm text-gray-600 mb-4">
                                    <span class="font-medium">Stock:</span> 
                                    <span class="{{ $book->stock_quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $book->stock_quantity }} units
                                    </span>
                                </p>
                                    <x-delete-and-edit :book="$book"/>
                            </div>
                        </div>
                    @endforeach
                </div>


            @endif
        </div>
    </div>

    <!-- Include Book Modal (Add/Edit) -->
    @include('admin.add-book-modal')


</x-admin-layout>