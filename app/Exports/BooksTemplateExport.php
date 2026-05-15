<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BooksTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['ISBN', 'Title', 'Author', 'Price', 'Stock', 'Category', 'Description'];
    }

    public function array(): array
    {
        return [
            ['9780141182636', 'Sample Book Title', 'Sample Author', '499.99', '10', 'Fiction', 'Short description'],
        ];
    }
}
