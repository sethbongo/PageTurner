<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Repositories\BookRepository;
use App\Http\Resources\BookResource;

class BookController extends Controller
{
    protected BookRepository $repository;

    public function __construct(BookRepository $repository)
    {
        $this->repository = $repository;
    }

    public function get_books(){
        $books = $this->repository->getActiveCatalog(12);
        
        return view('welcome', compact('books'));
    }

    public function logged_in_get_books(){
        $books = $this->repository->getActiveCatalog(12);
        
        return view('dashboard', compact('books'));
    }

    public function search(){
        $query = request('query');
        
        if (!$query) {
            return redirect()->back();
        }
        
        $searchTerm = strtolower($query);
        
        // Instead of searching all, use Scout if available or standard query
        $books = Book::with('category')
            ->where(function($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchTerm}%"])
                  ->orWhereRaw('LOWER(author) LIKE ?', ["%{$searchTerm}%"]);
            })
            ->latest('updated_at')
            ->paginate(12)
            ->appends(['query' => $query]);
        
        if (auth()->check()) {
            if (auth()->user()->role === 'admin') {
                $categories = Category::all();
                return view('admin.manage-books', compact('books', 'categories', 'query'));
            }
            return view('dashboard', compact('books', 'query'));
        }
        
        return view('welcome', compact('books', 'query'));
    }

    public function books_details($id)
    {
        // Don't load all reviews for 1 million records, just get the book with category
        $book = Book::with(['category', 'reviews' => function($q) {
            $q->latest()->limit(5); // Only load 5 recent reviews to save memory
        }])->findOrFail($id);
        
        if (auth()->check()) {
            // Check if user is admin
            if (auth()->user()->role === 'admin') {
                $categories = Category::all();
                // NEVER use get() on a 1 million row table! Limit to 10 for display purposes.
                $books = Book::latest('updated_at')->limit(10)->get();
                return view('books.admin-books', compact('book', 'categories', 'books'));
            }
            return view('books.auth-books', compact('book'));
        }
        return view('books.guest-books', compact('book'));
    }

}
