<?php

namespace App\Exports;

use App\Models\Book;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BooksExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, ShouldQueue
{
    private array $filters;
    private array $columns;

    private array $columnMap = [
        'isbn' => 'ISBN',
        'title' => 'Title',
        'author' => 'Author',
        'price' => 'Price',
        'stock' => 'Stock',
        'category' => 'Category',
        'description' => 'Description',
        'created_at' => 'Created At',
    ];

    public function __construct(array $filters = [], array $columns = [])
    {
        $this->filters = $filters;
        $this->columns = $columns ?: array_keys($this->columnMap);
    }

    public function query(): Builder
    {
        $query = Book::query()->with('category');

        if (!empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if (!empty($this->filters['price_min'])) {
            $query->where('price', '>=', $this->filters['price_min']);
        }

        if (!empty($this->filters['price_max'])) {
            $query->where('price', '<=', $this->filters['price_max']);
        }

        if (!empty($this->filters['stock_status'])) {
            if ($this->filters['stock_status'] === 'in_stock') {
                $query->where('stock_quantity', '>', 0);
            }
            if ($this->filters['stock_status'] === 'out_of_stock') {
                $query->where('stock_quantity', '=', 0);
            }
            if ($this->filters['stock_status'] === 'low_stock') {
                $query->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10);
            }
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('id');
    }

    public function headings(): array
    {
        return array_map(fn($key) => $this->columnMap[$key], $this->columns);
    }

    public function map($book): array
    {
        $values = [
            'isbn' => $book->isbn,
            'title' => $book->title,
            'author' => $book->author,
            'price' => $book->price,
            'stock' => $book->stock_quantity,
            'category' => optional($book->category)->name,
            'description' => $book->description,
            'created_at' => optional($book->created_at)->format('Y-m-d H:i:s'),
        ];

        return array_map(fn($key) => $values[$key] ?? null, $this->columns);
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
