<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReporteExport implements FromArray, WithHeadings
{
    public function __construct(private array $filas) {}
    public function array(): array { return $this->filas; }
    public function headings(): array { return array_keys($this->filas[0] ?? ['Sin resultados' => '']); }
}
