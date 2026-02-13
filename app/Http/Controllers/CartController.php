<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use function PHPUnit\Framework\returnArgument;

class CartController extends Controller
{
    public function cart_view(){
        return view('cart.cart');
    }

    public function add_to_cart(Request $request){
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $book = Book::findOrFail($request->book_id);
        $requestedQuantity = (int) $request->quantity;

        if ($book->stock_quantity < $requestedQuantity) {
            return redirect()->back()->withErrors(['quantity' => 'Not enough stock available.']);
        }

        $cartOrder = Order::firstOrCreate([
            'user_id' => auth()->id(),
            'status' => 'Cart'
        ], [
            'total_amount' => 0.00
        ]);

        $existingCartItem = OrderItem::where([
            'order_id' => $cartOrder->id,
            'book_id' => $book->id
        ])->first();

        if ($existingCartItem) {
            $newQuantity = $existingCartItem->quantity + $requestedQuantity;
            
            if ($newQuantity > $book->stock_quantity) {
                return redirect()->back()->withErrors(['quantity' => 'Adding this quantity would exceed available stock.']);
            }
            
            $existingCartItem->update([
                'quantity' => $newQuantity
            ]);
        } else {
            OrderItem::create([
                'order_id' => $cartOrder->id,
                'book_id' => $book->id,
                'quantity' => $requestedQuantity,
                'unit_price' => $book->price 
            ]);
        }

        $cartTotal = OrderItem::where('order_id', $cartOrder->id)
            ->join('books', 'order_items.book_id', '=', 'books.id')
            ->sum(\DB::raw('order_items.quantity * books.price'));
        
        $cartOrder->update([
            'total_amount' => $cartTotal
        ]);

        return redirect()->route('dashboard')->with('success', 'Item added to cart successfully!');
    }
}
