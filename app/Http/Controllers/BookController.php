<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function get_books(){
        $books = Book::with('category')->get();
        return view('welcome', compact('books'));
    }

    public function logged_in_get_books(){
        $books = Book::with('category')->get();
        return view('dashboard', compact('books'));
    }

public function books_details($id)
{
    $book = Book::with('category', 'reviews')->findOrFail($id);
    if (auth()->check()) {
        return view('books.show', compact('book'));
    }
    return view('books.guest-books', compact('book'));
}

}
