<?php

use App\Http\Controllers\Api\BookApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api', 'ApiRateLimiter:api', 'ApiTransformResponse', 'ETagCaching'])->group(function () {
    /**
     * Public endpoints (no auth required)
     */
    Route::get('/books', [BookApiController::class, 'index'])->name('api.books.index');
    Route::get('/books/search', [BookApiController::class, 'search'])->name('api.books.search');
    Route::get('/books/{book}', [BookApiController::class, 'show'])->name('api.books.show');

    /**
     * Alternative pagination endpoint
     */
    Route::get('/books/pagination/offset', [BookApiController::class, 'indexOffset'])->name('api.books.index-offset');

    /**
     * Protected endpoints (auth required)
     */
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/books', [BookApiController::class, 'store'])->name('api.books.store');
        Route::put('/books/{book}', [BookApiController::class, 'update'])->name('api.books.update');
        Route::delete('/books/{book}', [BookApiController::class, 'destroy'])->name('api.books.destroy');
    });
});

/**
 * Auth endpoints with strict rate limiting
 */
Route::prefix('api/v1')->middleware(['api', 'ApiRateLimiter:login'])->group(function () {
    // Login endpoint will be added to existing auth routes
});
