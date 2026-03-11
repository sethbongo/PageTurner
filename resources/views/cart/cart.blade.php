<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Enjoy your books!</h2>
    </x-slot>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Your Cart</h1>

        <x-flash-messages/>

        @if($order_items->count() > 0)
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($order_items as $item)
                            <div class="flex items-center border-b pb-4">
                                <img src="{{ $item->book->cover_image ? asset('storage/' . $item->book->cover_image) : '/images/no-cover.jpg' }}" alt="{{ $item->book->title }}" class="w-16 h-24 object-cover mr-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold">{{ $item->book->title }}</h3>
                                    <p class="text-gray-600">by {{ $item->book->author }}</p>
                                    <p class="text-gray-800 font-bold">${{ number_format($item->book->price, 2) }}</p>
                                    <p class="text-sm text-gray-500">Stock: {{ $item->book->stock_quantity }} available</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <form action="{{ route('cart.update', $item->id) }}" method="POST" class="flex items-center space-x-2">
                                        @csrf
                                        @method('PATCH')
                                        <label for="quantity-{{ $item->id }}" class="text-sm">Qty:</label>
                                        <input 
                                            type="number" 
                                            id="quantity-{{ $item->id }}" 
                                            name="quantity"
                                            value="{{ $item->quantity }}" 
                                            min="1" 
                                            max="{{ $item->book->stock_quantity }}"
                                            class="w-16 px-2 py-1 border rounded"
                                            onchange="this.form.submit()"
                                        >
                                    </form>
                                    <form action="{{ route('cart.remove', $item->id) }}" method="POST" onsubmit="return confirm('Remove this item from cart?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700">Remove</button>
                                    </form>
                                </div>
                                <div class="ml-4 text-right">
                                    <p class="text-lg font-bold">${{ number_format($item->book->price * $item->quantity, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                        <div class="flex justify-between text-xl font-bold mt-2">
                            <span>Total:</span>
                            <span>${{ number_format($order->total_amount, 2) }}</span>
                        </div>

                    <div class="mt-6 flex justify-between">
                        <a href="{{ route('dashboard') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Continue Shopping</a>
                        <button onclick="openCheckoutModal()" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Proceed to Checkout</button>
                    </div>
                </div>
            </div>

            <!-- Include Checkout Modal -->
            @include('cart.checkout-modal')
        @else
            <div class="text-center py-12">
                <p class="text-gray-500 text-lg">Your cart is empty.</p>
                <a href="{{ route('dashboard') }}" class="mt-4 inline-block bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Start Shopping</a>
            </div>
        @endif
    </div>
</x-app-layout>
