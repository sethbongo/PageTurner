<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;

class BookController extends Controller
{
    public function get_books(){
        $books = Book::with('category', 'reviews')->latest()->paginate(12);
        
        return view('welcome', compact('books'));
    }

    public function logged_in_get_books(){
        $books = Book::with('category', 'reviews')->latest()->paginate(12);
        
        return view('dashboard', compact('books'));
    }

    public function search(){
        $query = request('query');
        
        if (!$query) {
            return redirect()->back();
        }
        
        $searchTerm = strtolower($query);
        
        $books = Book::with('category', 'reviews')
            ->where(function($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereRaw('LOWER(author) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereHas('category', function($subq) use ($searchTerm) {
                      $subq->whereRaw('LOWER(name) LIKE ?', ["%{$searchTerm}%"]);
                  });
            })
            ->latest()
            ->paginate(12)
            ->appends(['query' => $query]);
        
        if (auth()->check()) {
            if (auth()->user()->role === 'admin') {
                return view('admin.admin_home', compact('books', 'query'));
            }
            return view('dashboard', compact('books', 'query'));
        }
        
        return view('welcome', compact('books', 'query'));
    }

public function books_details($id)
{
    $book = Book::with('category', 'reviews.user')->findOrFail($id);
    
    if (auth()->check()) {
        // Check if user is admin
        if (auth()->user()->role === 'admin') {
            $categories = Category::all();
            $books = Book::all();
            return view('books.admin-books', compact('book', 'categories', 'books'));
        }
        return view('books.auth-books', compact('book'));
    }
    return view('books.guest-books', compact('book'));
}

}
