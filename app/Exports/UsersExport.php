<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize
{
    private bool $redact;

    public function __construct(bool $redact = false)
    {
        $this->redact = $redact;
    }

    public function query(): Builder
    {
        return User::query()->orderBy('id');
    }

    public function headings(): array
    {
        return ['First Name', 'Middle Name', 'Last Name', 'Suffix', 'Email', 'Role', 'Created At'];
    }

    public function map($user): array
    {
        if ($this->redact) {
            return [
                $this->maskValue($user->first_name),
                $this->maskValue($user->middle_name),
                $this->maskValue($user->last_name),
                $this->maskValue($user->suffix),
                $this->maskEmail($user->email),
                $user->role,
                optional($user->created_at)->format('Y-m-d H:i:s'),
            ];
        }

        return [
            $user->first_name,
            $user->middle_name,
            $user->last_name,
            $user->suffix,
            $user->email,
            $user->role,
            optional($user->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    private function maskValue(?string $value): string
    {
        if (!$value) {
            return '';
        }

        $length = strlen($value);
        if ($length <= 2) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 1) . str_repeat('*', $length - 2) . substr($value, -1);
    }

    private function maskEmail(?string $email): string
    {
        if (!$email || !str_contains($email, '@')) {
            return $this->maskValue($email ?? '');
        }

        [$name, $domain] = explode('@', $email, 2);
        return $this->maskValue($name) . '@' . $domain;
    }
}
