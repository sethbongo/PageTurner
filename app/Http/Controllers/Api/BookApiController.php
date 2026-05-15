<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Book;
use Illuminate\Http\Request;

class BookApiController extends Controller
{
    protected $apiResponse;

    public function __construct(ApiResponse $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

    /**
     * Get all books with cursor pagination
     * GET /api/books?cursor=&per_page=20&fields=id,title,price&order_by=id&sort=asc
     */
    public function index(Request $request)
    {
        $query = Book::query();

        // Optional filtering
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%");
        }

        // Use cursor pagination
        return $this->apiResponse->paginated($query, null, 'Books retrieved successfully');
    }

    /**
     * Get books with offset pagination (alternative)
     * GET /api/books/offset?page=1&per_page=20&fields=id,title,price
     */
    public function indexOffset(Request $request)
    {
        $query = Book::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%");
        }

        return $this->apiResponse->offsetPaginated($query, null, 'Books retrieved successfully');
    }

    /**
     * Get single book
     * GET /api/books/{id}?fields=id,title,price,author
     */
    public function show(Book $book)
    {
        return $this->apiResponse->success($book, 'Book retrieved successfully');
    }

    /**
     * Create book
     * POST /api/books
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|unique:books',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $book = Book::create($validated);

        return $this->apiResponse->created($book, 'Book created successfully');
    }

    /**
     * Update book
     * PUT /api/books/{id}
     */
    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'title' => 'sometimes|string|max:255',
            'author' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'stock_quantity' => 'sometimes|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $book->update($validated);

        return $this->apiResponse->updated($book, 'Book updated successfully');
    }

    /**
     * Delete book
     * DELETE /api/books/{id}
     */
    public function destroy(Book $book)
    {
        $book->delete();

        return $this->apiResponse->deleted('Book deleted successfully');
    }

    /**
     * Search books
     * GET /api/books/search?q=laravel&fields=id,title,author
     */
    public function search(Request $request)
    {
        $query = $request->query('q', '');

        if (strlen($query) < 2) {
            return $this->apiResponse->error(
                'Search query must be at least 2 characters',
                'INVALID_SEARCH',
                400
            );
        }

        $books = Book::where('title', 'like', "%{$query}%")
            ->orWhere('author', 'like', "%{$query}%")
            ->orWhere('isbn', 'like', "%{$query}%")
            ->limit(50)
            ->get();

        return $this->apiResponse->success(
            $books,
            'Search results retrieved successfully'
        );
    }
}
