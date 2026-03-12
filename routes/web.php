<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchasedBooksController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BookController::class, 'get_books'])->name('guest_books');
Route::get('/search', [BookController::class, 'search'])->name('books.search');


Route::get('/dashboard', [BookController::class, 'logged_in_get_books'])
->middleware(['auth', 'verified', 'role_redirect:customer'])
->name('dashboard');

Route::get('/my-dashboard', [CustomerDashboardController::class, 'index'])
->middleware(['auth', 'verified', 'access_control:customer'])
->name('customer.dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::middleware('access_control:admin')->group(function () {
 Route::get('/admin_home', [AdminController::class, 'admin_home'])
->name('admin_home');
 Route::post('/admin/books', [AdminController::class, 'storeBook'])->name('admin.books.store');
 Route::post('/admin/categories', [AdminController::class, 'storeCategory'])->name('admin.categories.store');

  Route::get('/manage_books', [AdminController::class, 'manage_books'])->name('admin.manage_books');
  Route::put('/admin/books/{book}', [AdminController::class, 'updateBook'])->name('admin.books.update');
  Route::delete('/admin/books/{book}', [AdminController::class, 'deleteBook'])->name('admin.books.delete');

  Route::get('/manage_categories', [AdminController::class, 'manage_categories'])->name('admin.manage_categories');
  Route::put('/admin/categories/{category}', [AdminController::class, 'updateCategory'])->name('admin.categories.update');
  Route::delete('/admin/categories/{category}', [AdminController::class, 'deleteCategory'])->name('admin.categories.delete');


Route::get('/customer_orders', [AdminController::class, 'customer_orders'])->name('admin.customer_orders');
Route::patch('/admin/orders/{order}', [AdminController::class, 'updateOrderStatus'])->name('admin.orders.update');

});


Route::middleware(['access_control:customer', 'verified'])->group(function () {
 Route::get('/cart', [CartController::class, 'cart_view'])->name('cart');
Route::patch('/cart/update/{orderItem}', [CartController::class, 'update_quantity'])->name('cart.update');
Route::delete('/cart/remove/{orderItem}', [CartController::class, 'remove_from_cart'])->name('cart.remove');
Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');

Route::get('/orders', [OrderController::class, 'show_orders'])->name('orders.show');
Route::patch('/orders/cancel/{order}', [OrderController::class, 'cancel'])->name('orders.cancel');

Route::get('/purchased-books', [PurchasedBooksController::class, 'index'])->name('purchased-books.show');
Route::post('/purchased-books/{book}/review', [PurchasedBooksController::class, 'storeReview'])->name('purchased-books.review');

Route::post('/add_to_cart', [CartController::class, 'add_to_cart' ])->name('add-to-cart');

});





Route::get('/book_details/{id}', [BookController::class, 'books_details'])->name('get_books_details');

require __DIR__.'/auth.php';

