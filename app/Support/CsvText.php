<?php

namespace App\Support;

class CsvText
{
    /**
     * CSVs exported by older spreadsheet applications are often Windows-1252,
     * while MySQL text columns require UTF-8 input.
     */
    public static function normalize(mixed $value): mixed
    {
        if (! is_string($value) || $value === '' || mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }
}
