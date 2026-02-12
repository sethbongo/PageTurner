<x-app-layout>


<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Enjoy your books!</h2>
</x-slot>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Your Cart</h1>

    @php
        // Dummy cart data
        $cartItems = [
            [
                'id' => 1,
                'title' => 'The Great Gatsby',
                'author' => 'F. Scott Fitzgerald',
                'price' => 12.99,
                'quantity' => 2,
                'image' => 'https://via.placeholder.com/100x150' // Placeholder image
            ],
            [
                'id' => 2,
                'title' => 'To Kill a Mockingbird',
                'author' => 'Harper Lee',
                'price' => 14.99,
                'quantity' => 1,
                'image' => 'https://via.placeholder.com/100x150'
            ],
            [
                'id' => 3,
                'title' => '1984',
                'author' => 'George Orwell',
                'price' => 13.50,
                'quantity' => 3,
                'image' => 'https://via.placeholder.com/100x150'
            ]
        ];

        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $tax = $subtotal * 0.08; // 8% tax
        $total = $subtotal + $tax;
    @endphp

    @if(count($cartItems) > 0)
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($cartItems as $item)
                        <div class="flex items-center border-b pb-4">
                            <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" class="w-16 h-24 object-cover mr-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold">{{ $item['title'] }}</h3>
                                <p class="text-gray-600">by {{ $item['author'] }}</p>
                                <p class="text-gray-800 font-bold">${{ number_format($item['price'], 2) }}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <label for="quantity-{{ $item['id'] }}" class="text-sm">Qty:</label>
                                <input type="number" id="quantity-{{ $item['id'] }}" value="{{ $item['quantity'] }}" min="1" class="w-16 px-2 py-1 border rounded">
                                <button class="text-red-500 hover:text-red-700">Remove</button>
                            </div>
                            <div class="ml-4 text-right">
                                <p class="text-lg font-bold">${{ number_format($item['price'] * $item['quantity'], 2) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 border-t pt-4">
                    <div class="flex justify-between text-lg">
                        <span>Subtotal:</span>
                        <span>${{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg">
                        <span>Tax (8%):</span>
                        <span>${{ number_format($tax, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-xl font-bold mt-2">
                        <span>Total:</span>
                        <span>${{ number_format($total, 2) }}</span>
                    </div>
                </div>

                <div class="mt-6 flex justify-between">
                    <a href="" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Continue Shopping</a>
                    <button class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Proceed to Checkout</button>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <p class="text-gray-500 text-lg">Your cart is empty.</p>
            <a href="" class="mt-4 inline-block bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Start Shopping</a>
        </div>
    @endif
</div>

</x-app-layout>