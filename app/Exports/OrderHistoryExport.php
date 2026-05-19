<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OrderHistoryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    public function collection()
    {
        return $this->orders->flatMap(function ($order) {
            return $order->orderItems->map(function ($item) use ($order) {
                return [
                    'order' => $order,
                    'item' => $item,
                ];
            });
        });
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Order Date',
            'Status',
            'Book Title',
            'Author',
            'Category',
            'Quantity',
            'Unit Price',
            'Total',
            'ISBN',
        ];
    }

    public function map($row): array
    {
        $order = $row['order'];
        $item = $row['item'];

        return [
            $order->id,
            $order->created_at->format('Y-m-d H:i:s'),
            $order->status,
            $item->book->title,
            $item->book->author,
            $item->book->category->name,
            $item->quantity,
            number_format($item->price, 2),
            number_format($item->quantity * $item->price, 2),
            $item->book->isbn,
        ];
    }
}
