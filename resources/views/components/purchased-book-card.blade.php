@props(['book'])

<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
    <!-- Book Image -->
    <a href="{{ route('get_books_details', $book->id) }}">
        @if($book->cover_image)
            <img src="{{ asset($book->cover_image) }}" alt="{{ $book->title }}" class="w-full h-64 object-cover">
        @else
            <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                <svg class="w-20 h-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
        @endif
    </a>
    
    <div class="p-4">
        <!-- Book Title -->
        <a href="{{ route('get_books_details', $book->id) }}" class="block hover:text-blue-600 transition-colors">
            <h3 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2">{{ $book->title }}</h3>
        </a>
        
        <p class="text-sm text-gray-600 mb-3">
            <span class="font-medium">By:</span> {{ $book->author }}
        </p>
        
        <p class="text-sm text-gray-600 mb-3">
            <span class="font-medium">Category:</span> {{ $book->category->name }}
        </p>
        
        <!-- Book Overall Rating -->
        @if($book->reviewCount > 0)
        <div class="mb-3 flex items-center">
            <div class="flex items-center">
                @for($i = 1; $i <= 5; $i++)
                    <x-rating :userReview="round($book->averageRating)" :i="$i"/>
                @endfor
            </div>
            <span class="ml-2 text-sm text-gray-600">
                {{ number_format($book->averageRating, 1) }} ({{ $book->reviewCount }} {{ $book->reviewCount == 1 ? 'review' : 'reviews' }})
            </span>
        </div>
        @else
        <div class="mb-3">
            <span class="text-sm text-gray-500 italic">No reviews yet</span>
        </div>
        @endif
        
        <div class="mb-4">
            <span class="text-2xl font-bold text-green-600">${{ number_format($book->price, 2) }}</span>
        </div>
        
        <!-- Review Section -->
        @if($book->userReview)
            <!-- User has already reviewed -->
            <div class="bg-green-50 border border-green-600 rounded-lg p-3">
                <div class="flex items-center mb-2">
                    <span class="text-sm font-semibold">Your Review</span>
                    <div class="ml-auto flex items-center">
                        @for($i = 1; $i <= 5; $i++)
                       <x-rating :userReview="$book->userReview->rating" :i="$i"/>
                        @endfor
                    </div>
                </div>
                <p class="text-sm text-gray-700">{{ $book->userReview->comment }}</p>
            </div>
        @else
            <!-- Show review button -->
            <button 
                onclick="openReviewModal{{ $book->id }}()" 
                class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-semibold"
            >
                Write a Review
            </button>
        @endif
    </div>
</div>

<!-- Review Modal -->
@if(!$book->userReview)
<div id="reviewModal{{ $book->id }}" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Review: {{ $book->title }}</h3>
            <button onclick="closeReviewModal{{ $book->id }}()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form action="{{ route('purchased-books.review', $book->id) }}" method="POST">
            @csrf
            
            <!-- Rating -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                <div class="flex space-x-2">
                    @for($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer">
                            <input type="radio" name="rating" value="{{ $i }}" class="hidden peer" required>
                            <svg class="w-8 h-8 text-gray-300 peer-checked:text-yellow-400 hover:text-yellow-300 transition-colors fill-current" viewBox="0 0 20 20">
                                <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                            </svg>
                        </label>
                    @endfor
                </div>
                @error('rating')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Comment -->
            <div class="mb-4">
                <label for="comment{{ $book->id }}" class="block text-sm font-medium text-gray-700 mb-2">Your Review</label>
                <textarea 
                    id="comment{{ $book->id }}" 
                    name="comment" 
                    rows="4" 
                    required
                    maxlength="1000"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Share your thoughts about this book..."
                ></textarea>
                @error('comment')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Buttons -->
            <div class="flex space-x-3">
                <button 
                    type="button" 
                    onclick="closeReviewModal{{ $book->id }}()"
                    class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors font-semibold"
                >
                    Cancel
                </button>
                <button 
                    type="submit"
                    class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-semibold"
                >
                    Submit Review
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openReviewModal{{ $book->id }}() {
        document.getElementById('reviewModal{{ $book->id }}').classList.remove('hidden');
    }
    
    function closeReviewModal{{ $book->id }}() {
        document.getElementById('reviewModal{{ $book->id }}').classList.add('hidden');
    }
</script>
@endif
