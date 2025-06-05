<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CashStockExport implements WithMultipleSheets
{
    protected $start;
    protected $end;

    public function __construct(Carbon $start, Carbon $end)
    {
        $this->start = $start;
        $this->end   = $end;
    }

    public function sheets(): array
    {
        return [
            // First sheet: summary per product
            new SectionSummarySheet($this->start, $this->end),

            // Second sheet: detail of all activities
            new SectionDetailSheet($this->start, $this->end),
        ];
    }
}
