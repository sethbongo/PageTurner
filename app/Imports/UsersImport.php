<?php

namespace App\Imports;

use App\Models\ImportExportLog;
use App\Models\ImportFailure;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Hash;
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
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class UsersImport implements OnEachRow, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsOnFailure, SkipsEmptyRows, WithEvents, ShouldQueue
{
    use SkipsFailures;

    private int $logId;
    private string $defaultRole;

    public function __construct(int $logId, string $defaultRole = 'customer')
    {
        $this->logId = $logId;
        $this->defaultRole = $defaultRole;
    }

    public function onRow(Row $row): void
    {
        $data = $row->toArray();

        $role = $data['role'] ?? $this->defaultRole;
        $password = $data['password'] ?? Str::random(12);

        User::create([
            'first_name' => trim((string) ($data['first_name'] ?? '')),
            'middle_name' => trim((string) ($data['middle_name'] ?? '')),
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'suffix' => trim((string) ($data['suffix'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'password' => Hash::make((string) $password),
            'role' => $role ?: $this->defaultRole,
        ]);

        ImportExportLog::whereKey($this->logId)->increment('processed_rows');
    }

    public function rules(): array
    {
        return [
            '*.first_name' => ['required', 'string', 'max:255'],
            '*.middle_name' => ['nullable', 'string', 'max:255'],
            '*.last_name' => ['required', 'string', 'max:255'],
            '*.suffix' => ['nullable', 'string', 'max:50'],
            '*.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            '*.role' => ['nullable', Rule::in(['admin', 'customer'])],
            '*.password' => ['nullable', 'string', 'min:8'],
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
        ];
    }
}
