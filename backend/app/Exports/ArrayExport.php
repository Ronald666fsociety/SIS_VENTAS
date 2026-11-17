<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ArrayExport implements FromArray, ShouldAutoSize
{
    public function __construct(private array $filas)
    {
    }

    public function array(): array
    {
        return $this->filas;
    }
}
