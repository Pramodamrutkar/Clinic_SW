<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\InternalReport;
use App\Exports\TopFunnelReport;
use App\Exports\MisDataReport;

class MultiSheetDataReport implements WithMultipleSheets 
{
    public function sheets(): array
    {
        return [
            'Main' => new InternalReport(),
            'Top Funnel' => new TopFunnelReport(),
            'Aggregated Top Funnel' => new AggTopFunnelReport(),
            "User Journey" => new MisDataReport()
        ];
    }

    
}
