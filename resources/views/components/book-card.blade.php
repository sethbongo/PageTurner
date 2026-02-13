@props(['book'])

<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
    <!-- Clickable Image -->
    <a href="{{ route('get_books_details', parameters: $book->id) }}">
        @if($book->cover_image)
            <img src="{{ asset($book->cover_image) }}" alt="{{ $book->title }}" class="w-full h-64 object-cover">
        @else
            <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                <svg class="w-20 h-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
        @endif
    </a>
    
    <div class="p-4">
        <!-- Clickable Title -->
        <a href="{{ route('get_books_details', parameters: $book->id) }}" class="block hover:text-blue-600 transition-colors">
            <h3 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2">{{ $book->title }}</h3>
        </a>
        
        <p class="text-sm text-gray-600 mb-3">
            <span class="font-medium">By:</span> {{ $book->author }}
        </p>
        
        <p class="text-sm text-gray-600 mb-3">
            <span class="font-medium">Category:</span> {{ $book->category->name }}
        </p>
        
        <div class="flex justify-between items-center mb-3">
            <span class="text-2xl font-bold text-green-600">${{ number_format($book->price, 2) }}</span>
            
            <div class="text-sm">
                @if($book->stock_quantity > 0)
                    <span class="text-black-700">
                        <span class="font-semibold">Stock left:</span> 
                        <span class="text-green-600">
                            {{ $book->stock_quantity }}
                        </span>
                    </span>
                @else
                    <span class="text-red-600 font-semibold">Out of Stock</span>
                @endif
            </div>
        </div>
        
        <!-- Add to Cart Button - Not wrapped in link -->
        <x-add-to-cart-button :book="$book"/>

    </div>
    
</div>