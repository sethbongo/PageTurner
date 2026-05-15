<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, ShouldQueue
{
    private array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Order::query()->with(['user', 'orderItems']);

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['customer_id'])) {
            $query->where('user_id', $this->filters['customer_id']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query->where('status', '!=', 'Cart')->orderBy('id');
    }

    public function headings(): array
    {
        return ['Order ID', 'Customer', 'Items', 'Total Amount', 'Status', 'Created At'];
    }

    public function map($order): array
    {
        return [
            $order->id,
            optional($order->user)->name,
            $order->orderItems->count(),
            $order->total_amount,
            $order->status,
            optional($order->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
