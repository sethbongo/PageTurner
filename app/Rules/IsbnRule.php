<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsbnRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isbn = preg_replace('/[^0-9Xx]/', '', (string) $value);

        if (!$isbn || (!$this->isIsbn10($isbn) && !$this->isIsbn13($isbn))) {
            $fail('The :attribute must be a valid ISBN-10 or ISBN-13.');
        }
    }

    private function isIsbn10(string $isbn): bool
    {
        if (strlen($isbn) !== 10) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $char = $isbn[$i];
            if ($i === 9 && ($char === 'X' || $char === 'x')) {
                $digit = 10;
            } elseif (ctype_digit($char)) {
                $digit = (int) $char;
            } else {
                return false;
            }
            $sum += (10 - $i) * $digit;
        }

        return $sum % 11 === 0;
    }

    private function isIsbn13(string $isbn): bool
    {
        if (strlen($isbn) !== 13 || !ctype_digit($isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $isbn[$i];
            $sum += $digit * ($i % 2 === 0 ? 1 : 3);
        }

        $check = (10 - ($sum % 10)) % 10;

        return $check === (int) $isbn[12];
    }
}
