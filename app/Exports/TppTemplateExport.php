<?php

namespace App\Exports;

class TppTemplateExport
{
    protected array $headings;
    protected array $rows;

    public function __construct(array $headings, array $rows)
    {
        $this->headings = $headings;
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function rows(): array
    {
        return $this->rows;
    }
}

