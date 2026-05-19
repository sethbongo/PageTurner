<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchasedBooksController;
use App\Http\Controllers\UserDataPortabilityController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BookController::class, 'get_books'])->name('guest_books');
Route::get('/search', [BookController::class, 'search'])->name('books.search');


Route::get('/dashboard', [BookController::class, 'logged_in_get_books'])
    ->middleware(['auth', 'verified', 'role_redirect:customer'])
    ->name('dashboard');

Route::get('/my-dashboard', [CustomerDashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'access_control:customer'])
    ->name('customer.dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::middleware('access_control:admin')->group(function () {
    Route::get('/admin_home', [AdminController::class, 'admin_home'])
        ->name('admin_home');
    Route::post('/admin/books', [AdminController::class, 'storeBook'])->name('admin.books.store');
    Route::post('/admin/categories', [AdminController::class, 'storeCategory'])->name('admin.categories.store');

    Route::get('/manage_books', [AdminController::class, 'manage_books'])->name('admin.manage_books');
    Route::put('/admin/books/{book}', [AdminController::class, 'updateBook'])->name('admin.books.update');
    Route::delete('/admin/books/{book}', [AdminController::class, 'deleteBook'])->name('admin.books.delete');

    Route::get('/manage_categories', [AdminController::class, 'manage_categories'])->name('admin.manage_categories');
    Route::put('/admin/categories/{category}', [AdminController::class, 'updateCategory'])->name('admin.categories.update');
    Route::delete('/admin/categories/{category}', [AdminController::class, 'deleteCategory'])->name('admin.categories.delete');


    Route::get('/customer_orders', [AdminController::class, 'customer_orders'])->name('admin.customer_orders');
    Route::patch('/admin/orders/{order}', [AdminController::class, 'updateOrderStatus'])->name('admin.orders.update');

    Route::get('/admin/import-export', [ImportExportController::class, 'index'])->name('admin.imports.index');
    Route::get('/admin/imports/books/template', [ImportExportController::class, 'downloadBookTemplate'])->name('admin.imports.books.template');
    Route::post('/admin/imports/books', [ImportExportController::class, 'importBooks'])->name('admin.imports.books');
    Route::post('/admin/exports/books', [ImportExportController::class, 'exportBooks'])->name('admin.exports.books');
    Route::post('/admin/exports/orders', [ImportExportController::class, 'exportOrders'])->name('admin.exports.orders');
    Route::post('/admin/exports/financial', [ImportExportController::class, 'exportFinancialReport'])->name('admin.exports.financial');
    Route::get('/admin/imports/users/template', [ImportExportController::class, 'downloadUserTemplate'])->name('admin.imports.users.template');
    Route::post('/admin/imports/users', [ImportExportController::class, 'importUsers'])->name('admin.imports.users');
    Route::post('/admin/exports/users', [ImportExportController::class, 'exportUsers'])->name('admin.exports.users');
    Route::get('/admin/imports/{log}/failures', [ImportExportController::class, 'downloadImportFailures'])->name('admin.imports.failures');
    Route::get('/admin/exports/{log}/download', [ImportExportController::class, 'downloadExportFile'])->name('admin.exports.download');

    // Backup and Maintenance Routes (4.2)
    Route::get('/admin/backups', [BackupController::class, 'index'])->name('admin.backups.index');
    Route::post('/admin/backups/trigger', [BackupController::class, 'trigger'])->name('admin.backups.trigger');
    Route::post('/admin/backups/cleanup', [BackupController::class, 'cleanup'])->name('admin.backups.cleanup');
    Route::get('/admin/backups/{filename}/download', [BackupController::class, 'download'])->name('admin.backups.download');
    Route::delete('/admin/backups/{filename}', [BackupController::class, 'delete'])->name('admin.backups.delete');

    // Audit Logging and Compliance Routes (4.3)
    Route::get('/admin/audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('/admin/audit/{auditLog}', [AuditController::class, 'show'])->name('audit.show');
    Route::get('/admin/audit/export/pdf', [AuditController::class, 'exportPdf'])->name('audit.export-pdf');
    Route::get('/admin/audit/api/statistics', [AuditController::class, 'statistics'])->name('audit.statistics');
    Route::get('/admin/audit/api/critical', [AuditController::class, 'recentCritical'])->name('audit.recent-critical');

    // Enhanced Admin Dashboard Routes (9.1)
    Route::get('/admin/dashboard/enhanced', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/dashboard/clear-cache', [AdminDashboardController::class, 'clearCache'])->name('admin.dashboard.clear-cache');

});


Route::middleware(['access_control:customer', 'verified'])->group(function () {
    Route::get('/cart', [CartController::class, 'cart_view'])->name('cart');
    Route::patch('/cart/update/{orderItem}', [CartController::class, 'update_quantity'])->name('cart.update');
    Route::delete('/cart/remove/{orderItem}', [CartController::class, 'remove_from_cart'])->name('cart.remove');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');

    Route::get('/orders', [OrderController::class, 'show_orders'])->name('orders.show');
    Route::patch('/orders/cancel/{order}', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/{order}/invoice', [OrderController::class, 'downloadInvoice'])->name('orders.invoice');

    Route::get('/purchased-books', [PurchasedBooksController::class, 'index'])->name('purchased-books.show');
    Route::post('/purchased-books/{book}/review', [PurchasedBooksController::class, 'storeReview'])->name('purchased-books.review');

    Route::post('/add_to_cart', [CartController::class, 'add_to_cart'])->name('add-to-cart');

    // User Data Portability Routes (9.2)
    Route::get('/data-portability', [UserDataPortabilityController::class, 'dashboard'])->name('user.data-portability.dashboard');
    Route::get('/data-portability/exports', [UserDataPortabilityController::class, 'availableExports'])->name('user.data-portability.exports');
    Route::post('/data-portability/export/personal-json', [UserDataPortabilityController::class, 'exportPersonalDataJson'])->name('user.data-portability.export-personal-json');
    Route::post('/data-portability/export/orders-pdf', [UserDataPortabilityController::class, 'exportOrderHistoryPdf'])->name('user.data-portability.export-orders-pdf');
    Route::post('/data-portability/export/orders-excel', [UserDataPortabilityController::class, 'exportOrderHistoryExcel'])->name('user.data-portability.export-orders-excel');
    Route::post('/data-portability/export/reading-json', [UserDataPortabilityController::class, 'exportReadingHistoryJson'])->name('user.data-portability.export-reading-json');
    Route::post('/data-portability/export/reading-pdf', [UserDataPortabilityController::class, 'exportReadingHistoryPdf'])->name('user.data-portability.export-reading-pdf');
    Route::post('/data-portability/request-deletion', [UserDataPortabilityController::class, 'requestDataDeletion'])->name('user.data-portability.request-deletion');

});





Route::get('/book_details/{id}', [BookController::class, 'books_details'])->name('get_books_details');

require __DIR__ . '/auth.php';

