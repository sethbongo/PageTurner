@props(['book'])        

<button onclick="{{ $book->stock_quantity > 0 ? 'openAddToCartModal()' : '' }}"
    type="button"
    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-200
    {{ $book->stock_quantity == 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
    {{ $book->stock_quantity == 0 ? 'disabled' : '' }}
>
    {{ $book->stock_quantity == 0 ? 'Out of Stock' : 'Add to Cart' }}
</button>


