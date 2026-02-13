<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Available Books') }}
        </h2>
    </x-slot>
    
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(isset($books) && $books->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                    @foreach($books as $book)
                        <x-book-card :book="$book" />
                    @endforeach
                </div>
                @include('cart.add-to-cart-modal')

            @else
                <div class="text-center py-12">
                    <p class="text-gray-600 text-lg">No books available at the moment.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
