<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ReadingHistoryExportService
{
    /**
     * Export user's reading/browsing history as JSON
     */
    public function exportAsJson(User $user): string
    {
        $data = [
            'export_date' => now()->toIso8601String(),
            'user_name' => $user->name,
            'reading_history' => $this->getReadingHistory($user),
            'browsing_summary' => $this->getBrowsingSummary($user),
            'favorite_genres' => $this->getFavoriteGenres($user),
            'reading_statistics' => $this->getReadingStatistics($user),
        ];

        $filename = "reading-history-{$user->id}-" . now()->format('Y-m-d-His') . ".json";
        $path = "reading-exports/{$user->id}/{$filename}";

        Storage::disk('private')->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /**
     * Export user's reading history as PDF
     */
    public function exportAsPdf(User $user): string
    {
        $readingHistory = $this->getReadingHistory($user);
        $statistics = $this->getReadingStatistics($user);

        $filename = "reading-history-{$user->id}-" . now()->format('Y-m-d-His') . ".pdf";
        $path = "reading-exports/{$user->id}/{$filename}";

        $html = view('exports.reading-history-pdf', compact(
            'user',
            'readingHistory',
            'statistics'
        ))->render();

        $pdf = app('dompdf.wrapper')->loadHTML($html);
        Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Get reading history from orders/purchases
     */
    protected function getReadingHistory(User $user): array
    {
        return $user->orders()
            ->with(['orderItems.book.category'])
            ->where('status', '!=', 'cart')
            ->orderBy('created_at', 'desc')
            ->get()
            ->flatMap(function ($order) {
                return $order->orderItems->map(function ($item) use ($order) {
                    return [
                        'book_title' => $item->book->title,
                        'author' => $item->book->author,
                        'genre' => $item->book->category->name,
                        'purchase_date' => $order->created_at->toIso8601String(),
                        'isbn' => $item->book->isbn,
                    ];
                });
            })
            ->toArray();
    }

    /**
     * Get browsing summary if tracked
     */
    protected function getBrowsingSummary(User $user): array
    {
        // This would require view/browsing activity tracking
        return [
            'total_books_viewed' => $user->orders()->with('orderItems')->get()->sum(function ($order) {
                return $order->orderItems->count();
            }),
            'total_books_purchased' => $user->orders()
                ->where('status', '!=', 'cart')
                ->with('orderItems')
                ->get()
                ->sum(function ($order) {
                    return $order->orderItems->count();
                }),
        ];
    }

    /**
     * Get favorite genres based on purchases
     */
    protected function getFavoriteGenres(User $user): array
    {
        return $user->orders()
            ->with(['orderItems.book.category'])
            ->where('status', '!=', 'cart')
            ->get()
            ->flatMap(function ($order) {
                return $order->orderItems->map(function ($item) {
                    return $item->book->category->name;
                });
            })
            ->countBy()
            ->sort()
            ->reverse()
            ->take(5)
            ->toArray();
    }

    /**
     * Get reading statistics
     */
    protected function getReadingStatistics(User $user): array
    {
        $orders = $user->orders()
            ->with('orderItems')
            ->where('status', '!=', 'cart')
            ->get();

        $totalBooks = $orders->sum(function ($order) {
            return $order->orderItems->count();
        });

        $totalSpent = $orders->sum('total_amount');

        return [
            'total_books_owned' => $totalBooks,
            'total_amount_spent' => $totalSpent,
            'average_price_per_book' => $totalBooks > 0 ? $totalSpent / $totalBooks : 0,
            'reading_span_months' => $this->calculateReadingSpan($orders),
        ];
    }

    /**
     * Calculate reading span in months
     */
    protected function calculateReadingSpan($orders): int
    {
        if ($orders->isEmpty()) {
            return 0;
        }

        $firstOrder = $orders->min('created_at');
        $lastOrder = $orders->max('created_at');

        return $firstOrder->diffInMonths($lastOrder) + 1;
    }

    /**
     * Log the export request
     */
    public function logExportRequest(User $user, string $format): void
    {
        activity()
            ->performedBy($user)
            ->withProperties(['format' => $format, 'type' => 'reading_history_export'])
            ->log('user_exported_reading_history');
    }
}
