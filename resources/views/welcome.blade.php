@extends('layouts.guest-home')

@section('content')
        <div class="p-6">
            <x-flash-messages/>
            
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
                    <p class="text-gray-600 text-lg">No books available at the moment.</p>
                </div>
            @endif
        </div>
@endsection