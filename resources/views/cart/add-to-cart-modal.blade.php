@props(['book'])

<!-- Add to Cart Modal -->
<div id="add-to-cart-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
        <!-- Modal Header -->
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Add to Cart</h3>
            <button onclick="closeAddToCartModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Book Info -->
        <div class="mb-4">
            <h4 class="font-medium text-gray-900">{{ $book->title }}</h4>
            <p class="text-gray-600">{{ $book->author }}</p>
            <p class="text-green-600 font-semibold">${{ number_format($book->price, 2) }}</p>
            <p class="text-sm text-gray-500">Stock: {{ $book->stock_quantity }} available</p>
        </div>

        <!-- Add to Cart Form -->
        <form action="{{ route('cart.store') }}" method="POST">
            @csrf
            <input type="hidden" name="book_id" value="{{ $book->id }}">
            
            <div class="mb-4">
                <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                <input 
                    type="number" 
                    id="quantity" 
                    name="quantity" 
                    min="1" 
                    max="{{ $book->stock_quantity }}" 
                    value="1" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required
                >
            </div>

            <!-- Modal Actions -->
            <div class="flex gap-2">
                <button 
                    type="button" 
                    onclick="closeAddToCartModal()" 
                    class="flex-1 px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors duration-200"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors duration-200"
                >
                    Add to Cart
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddToCartModal() {
    const modal = document.getElementById('add-to-cart-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeAddToCartModal() {
    const modal = document.getElementById('add-to-cart-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Close modal when clicking outside
document.getElementById('add-to-cart-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddToCartModal();
    }
});
</script>