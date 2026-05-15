<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RevenueSummaryExport implements FromCollection, WithHeadings
{
    private array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function headings(): array
    {
        return ['Date', 'Orders', 'Revenue', 'Estimated Tax'];
    }

    public function collection(): Collection
    {
        $query = Order::query()->whereNot('status', 'Cart');

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        $taxRate = (float) config('reports.tax_rate', 0);

        $rows = $query
            ->selectRaw('DATE(created_at) as report_date, COUNT(*) as orders_count, SUM(total_amount) as revenue_sum')
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get()
            ->map(function ($row) use ($taxRate) {
                $revenue = (float) $row->revenue_sum;
                return [
                    $row->report_date,
                    (int) $row->orders_count,
                    number_format($revenue, 2, '.', ''),
                    number_format($revenue * $taxRate, 2, '.', ''),
                ];
            });

        return new Collection($rows);
    }
}
