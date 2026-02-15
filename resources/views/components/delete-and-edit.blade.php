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