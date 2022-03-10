<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use DB;

class AggTopFunnelReport  implements FromCollection,WithEvents,WithTitle,ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $repTopfunnel = DB::select('CALL sp_top_funnel(1)');
        
        $repTopfunnelArray = json_decode(json_encode($repTopfunnel, true),true);
     
        $headingsData[] = array_keys($repTopfunnelArray[0]);
        foreach ($repTopfunnelArray as $key => $value) {
            $headingsData[] = array(
                "MerchantTrackingId" => $value["MerchantTrackingId"],
                //"Date" => $value["Date"],
                "Sessions" => $value["Sessions"] ?? 0, 
                "OTPRequests" => $value["OTPRequests"] ?? 0,
                "OTPUsed" => $value["OTPUsed"] ?? 0,
                "Apps" => $value["Apps"] ?? 0,
                "AppsSubmitted" => $value["AppsSubmitted"] ?? 0
            );
        } 
     
        return collect($headingsData);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:AD1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ]
                ]);
            }
        ];
    }

    public function title(): string
    {
        $title = "Aggregated Top Funnel";
        return $title; 
    }
}
