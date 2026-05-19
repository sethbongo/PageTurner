<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;

class GdprDataExportService
{
    /**
     * Export user's personal data in GDPR-compliant JSON format
     */
    public function exportPersonalDataAsJson(User $user): string
    {
        $data = [
            'export_date' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'address' => $user->address ?? null,
                'created_at' => $user->created_at->toIso8601String(),
                'updated_at' => $user->updated_at->toIso8601String(),
            ],
            'purchase_history' => $this->getUserPurchaseHistory($user),
            'reviews' => $this->getUserReviews($user),
            'account_activity' => $this->getAccountActivity($user),
            'preferences' => $this->getUserPreferences($user),
        ];

        $filename = "personal-data-{$user->id}-" . now()->format('Y-m-d-His') . ".json";
        $path = "gdpr-exports/{$user->id}/{$filename}";

        Storage::disk('private')->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /**
     * Get user's purchase history
     */
    protected function getUserPurchaseHistory(User $user): array
    {
        return Order::where('user_id', $user->id)
            ->with(['orderItems.book'])
            ->get()
            ->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'order_date' => $order->created_at->toIso8601String(),
                    'items' => $order->orderItems->map(function ($item) {
                        return [
                            'book_title' => $item->book->title,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                        ];
                    })->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * Get user's reviews
     */
    protected function getUserReviews(User $user): array
    {
        return $user->reviews()
            ->with('book')
            ->get()
            ->map(function ($review) {
                return [
                    'book_title' => $review->book->title,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->toIso8601String(),
                ];
            })
            ->toArray();
    }

    /**
     * Get account activity and login history
     */
    protected function getAccountActivity(User $user): array
    {
        // This would require audit log tracking
        return [
            'last_login' => $user->last_login_at?->toIso8601String() ?? null,
            'login_count' => $user->login_count ?? 0,
            'last_activity_at' => $user->updated_at->toIso8601String(),
        ];
    }

    /**
     * Get user preferences
     */
    protected function getUserPreferences(User $user): array
    {
        return [
            'email_notifications' => $user->email_notifications_enabled ?? true,
            'newsletter_subscription' => $user->newsletter_subscribed ?? false,
            'two_factor_enabled' => !empty($user->two_factor_secret),
        ];
    }

    /**
     * Get the download path for the exported file
     */
    public function getDownloadPath(string $storagePath): string
    {
        return Storage::disk('private')->url($storagePath);
    }

    /**
     * Log the data export request
     */
    public function logExportRequest(User $user, string $format): void
    {
        // Log audit trail for GDPR compliance
        activity()
            ->performedBy($user)
            ->withProperties(['format' => $format, 'type' => 'personal_data_export'])
            ->log('user_exported_personal_data');
    }
}
