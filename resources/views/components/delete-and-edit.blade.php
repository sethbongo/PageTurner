  @props(['book', 'books'])
  
  <!-- Action Buttons -->
    <div class="flex gap-2">
        <!-- Edit Button -->
        <button onclick="openEditBookModal({{ $book->id }})" 
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-200">
            Edit
        </button>
        
        <!-- Delete Button -->
        <form action="{{ route('admin.books.delete', $book) }}" method="POST" 
                onsubmit="return confirm('Are you sure you want to delete this book? This action cannot be undone.');" 
                class="flex-1">
            @csrf
            @method('DELETE')
            <button type="submit" 
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-200">
                Delete
            </button>
        </form>
    </div>



<script>
    function openEditBookModal(bookId) {
        const books = @json($books);
        const book = books.find(b => b.id === bookId);
        
        if (book) {
            // Populate form fields
            document.getElementById('edit_book_id').value = book.id;
            document.getElementById('edit_title').value = book.title;
            document.getElementById('edit_author').value = book.author;
            document.getElementById('edit_category_id').value = book.category_id;
            document.getElementById('edit_isbn').value = book.isbn;
            document.getElementById('edit_price').value = book.price;
            document.getElementById('edit_stock_quantity').value = book.stock_quantity;
            document.getElementById('edit_description').value = book.description || '';
            
            // Update form action
            const form = document.getElementById('editBookForm');
            form.action = `/admin/books/${bookId}`;
            
            // Show modal
            document.getElementById('editBookModal').classList.remove('hidden');
        }
    }

    function closeEditBookModal() {
        document.getElementById('editBookModal').classList.add('hidden');
    }
</script>