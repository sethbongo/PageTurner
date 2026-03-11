@extends('layouts.guest-home')

@section('content')
        <div class="p-6">
            <x-flash-messages/>

            @if(isset($query) && $query)
                <div class="mb-6 max-w-7xl mx-auto flex items-center gap-3">
                    <a href="{{ route('guest_books') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        ← Back to All Books
                    </a>
                    <p class="text-gray-600">Found {{ $books->total() }} result(s) for "{{ $query }}"</p>
                </div>
            @endif
            
            @if(isset($books) && $books->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 max-w-7xl mx-auto">
                    @foreach($books as $book)
                    <form action="{{ route('login')  }}">
                        <x-book-card :book="$book" />
                    </form>
                    @endforeach
                </div>

                <!-- Pagination Links -->
                <div class="mt-6 flex justify-center">
                    {{ $books->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    @if(isset($query) && $query)
                        <p class="text-gray-600 text-lg">No books found matching "{{ $query }}".</p>
                    @else
                        <p class="text-gray-600 text-lg">No books available at the moment.</p>
                    @endif
                </div>
            @endif
        </div>
@endsection