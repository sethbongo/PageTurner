<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Orders</h2>
    </x-slot>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Order History</h1>

        <x-flash-messages/>

        @if($orders->count() > 0)
            <div class="space-y-6">
                @foreach($orders as $order)
                    <div class="bg-white shadow-md rounded-lg overflow-hidden">
                        
                        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                            <div class="flex items-center space-x-6">
                                <div>
                                    <p class="text-sm text-gray-600">Order ID</p>
                                    <p class="font-bold text-lg">#{{ $order->id }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Date</p>
                                    <p class="font-semibold">{{ $order->created_at->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total Amount</p>
                                    <p class="font-bold text-lg text-blue-600">${{ number_format($order->total_amount, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Status</p>
                                    <span class="inline-block text-sm font-semibold">
                                        {{ $order->status }}
                                    </span>
                                </div>
                            </div>
                            
                            @if(in_array($order->status, ['Pending', 'Processing']))
                                <form action="{{ route('orders.cancel', $order->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? Stock will be restored.')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 font-semibold">
                                        Cancel Order
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="p-6">
                            <h3 class="font-semibold text-lg mb-4">Order Items</h3>
                            <div class="space-y-3">
                                @foreach($order->orderItems as $item)
                                    <div class="flex items-center justify-between border-b pb-3">
                                        <div class="flex items-center space-x-4">
                                            <img src="{{ $item->book->image }}" alt="{{ $item->book->title }}" class="w-12 h-16 object-cover rounded">
                                            <div>
                                                <h4 class="font-semibold text-gray-900">{{ $item->book->title }}</h4>
                                                <p class="text-sm text-gray-600">by {{ $item->book->author }}</p>
                                                <p class="text-sm text-gray-500">Quantity: {{ $item->quantity }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-gray-900">${{ number_format($item->unit_price * $item->quantity, 2) }}</p>
                                            <p class="text-xs text-gray-500">${{ number_format($item->unit_price, 2) }} each</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-white shadow-md rounded-lg">
                <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-gray-500 text-lg mt-4">You haven't placed any orders yet.</p>
                <a href="{{ route('dashboard') }}" class="mt-4 inline-block bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">Start Shopping</a>
            </div>
        @endif
    </div>
</x-app-layout>
