<?php

namespace App\Imports;

use App\Models\Book;
use App\Models\Category;
use App\Models\ImportExportLog;
use App\Models\ImportFailure;
use App\Rules\IsbnRule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\ImportFailed;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class BooksImport implements OnEachRow, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsOnFailure, SkipsEmptyRows, WithEvents, ShouldQueue
{
    use SkipsFailures;

    private int $logId;
    private string $duplicateMode;

    public function __construct(int $logId, string $duplicateMode = 'skip')
    {
        $this->logId = $logId;
        $this->duplicateMode = $duplicateMode;
    }

    public function onRow(Row $row): void
    {
        $data = $row->toArray();

        $isbn = trim((string) ($data['isbn'] ?? ''));
        $categoryName = trim((string) ($data['category'] ?? ''));

        $category = Category::whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])->first();
        if (!$category) {
            return;
        }

        if ($this->duplicateMode !== 'update' && Book::where('isbn', $isbn)->exists()) {
            return;
        }

        $payload = [
            'category_id' => $category->id,
            'title' => trim((string) ($data['title'] ?? '')),
            'author' => trim((string) ($data['author'] ?? '')),
            'isbn' => $isbn,
            'price' => (float) ($data['price'] ?? 0),
            'stock_quantity' => (int) ($data['stock'] ?? 0),
            'description' => trim((string) ($data['description'] ?? '')),
        ];

        if ($this->duplicateMode === 'update') {
            Book::updateOrCreate(['isbn' => $isbn], $payload);
        } else {
            Book::create($payload);
        }

        ImportExportLog::whereKey($this->logId)->increment('processed_rows');
    }

    public function rules(): array
    {
        $isbnRules = ['required', 'max:20', new IsbnRule(), 'distinct'];
        if ($this->duplicateMode !== 'update') {
            $isbnRules[] = Rule::unique('books', 'isbn');
        }

        return [
            '*.isbn' => $isbnRules,
            '*.title' => ['required', 'string', 'max:255'],
            '*.author' => ['required', 'string', 'max:255'],
            '*.price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            '*.stock' => ['required', 'integer', 'min:0'],
            '*.category' => ['required', 'string', Rule::exists('categories', 'name')],
            '*.description' => ['nullable', 'string'],
        ];
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            ImportFailure::create([
                'import_export_log_id' => $this->logId,
                'row_number' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ]);

            ImportExportLog::whereKey($this->logId)->increment('failed_rows');
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (): void {
                ImportExportLog::whereKey($this->logId)->update([
                    'status' => 'completed',
                    'finished_at' => now(),
                ]);
            },
            ImportFailed::class => function (ImportFailed $event): void {
                $exception = $event->getException();

                ImportExportLog::whereKey($this->logId)->update([
                    'status' => 'failed',
                    'error_summary' => $exception->getMessage(),
                    'finished_at' => now(),
                ]);
            },
        ];
    }
}
