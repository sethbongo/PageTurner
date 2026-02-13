<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Models\Book;

Route::get('/', [BookController::class, 'get_books']);

Route::get('/dashboard', [BookController::class, 'get_books']
)->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', [BookController::class, 'logged_in_get_books'])->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::get('/cart', [CartController::class, 'cart_view'])->name('cart');


Route::post('/add_to_cart', [Cartcontroller::class, 'add_to_cart' ])->name('add-to-cart');

Route::get('/book_details/{id}', [BookController::class, 'books_details'])->name('get_books_details');
require __DIR__.'/auth.php';

