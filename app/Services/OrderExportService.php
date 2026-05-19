<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class OrderExportService
{
    /**
     * Export user's order history as Excel
     */
    public function exportAsExcel(User $user): string
    {
        $filename = "order-history-{$user->id}-" . now()->format('Y-m-d-His') . ".xlsx";
        $path = "order-exports/{$user->id}/{$filename}";

        $orders = Order::where('user_id', $user->id)
            ->with(['orderItems.book'])
            ->orderBy('created_at', 'desc')
            ->get();

        Excel::store(
            new \App\Exports\OrderHistoryExport($orders),
            $path,
            'private'
        );

        return $path;
    }

    /**
     * Export user's order history as PDF
     */
    public function exportAsPdf(User $user): string
    {
        $orders = Order::where('user_id', $user->id)
            ->with(['orderItems.book'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = "order-history-{$user->id}-" . now()->format('Y-m-d-His') . ".pdf";
        $path = "order-exports/{$user->id}/{$filename}";

        $html = view('exports.order-history-pdf', compact('orders', 'user'))->render();

        // Using dompdf
        $pdf = app('dompdf.wrapper')->loadHTML($html);
        Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Get summary of order statistics
     */
    public function getOrderSummary(User $user): array
    {
        $orders = Order::where('user_id', $user->id)
            ->where('status', '!=', 'cart')
            ->get();

        return [
            'total_orders' => $orders->count(),
            'total_spent' => $orders->sum('total_amount'),
            'average_order_value' => $orders->count() > 0 ? $orders->sum('total_amount') / $orders->count() : 0,
            'first_order_date' => $orders->min('created_at'),
            'last_order_date' => $orders->max('created_at'),
        ];
    }

    /**
     * Log the export request
     */
    public function logExportRequest(User $user, string $format): void
    {
        activity()
            ->performedBy($user)
            ->withProperties(['format' => $format, 'type' => 'order_history_export'])
            ->log('user_exported_order_history');
    }
}
