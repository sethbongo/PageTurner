<?php

namespace App\Exports;

use App\Models\ImportFailure;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportFailuresExport implements FromCollection, WithHeadings
{
    private int $logId;

    public function __construct(int $logId)
    {
        $this->logId = $logId;
    }

    public function headings(): array
    {
        return ['Row', 'Attribute', 'Errors', 'Values'];
    }

    public function collection(): Collection
    {
        $failures = ImportFailure::query()
            ->where('import_export_log_id', $this->logId)
            ->orderBy('row_number')
            ->get()
            ->map(function (ImportFailure $failure) {
                return [
                    $failure->row_number,
                    $failure->attribute,
                    is_array($failure->errors) ? implode('; ', $failure->errors) : $failure->errors,
                    json_encode($failure->values),
                ];
            });

        return new Collection($failures);
    }
}
