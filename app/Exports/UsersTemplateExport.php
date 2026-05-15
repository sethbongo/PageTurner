<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['First Name', 'Middle Name', 'Last Name', 'Suffix', 'Email', 'Role', 'Password'];
    }

    public function array(): array
    {
        return [
            ['Ada', '', 'Lovelace', '', 'ada@example.com', 'customer', 'password123'],
        ];
    }
}
