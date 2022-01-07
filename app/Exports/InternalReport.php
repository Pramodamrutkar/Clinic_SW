<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use DB;

class InternalReport implements FromCollection,WithEvents,WithTitle,ShouldAutoSize
{   

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $creditAppData = DB::select('CALL getCreditapp_stats()');
        $appDataArray = json_decode(json_encode($creditAppData, true),true);
        $headingsData[] = array_keys($appDataArray[0]);
        foreach ($appDataArray as $key => $value) {
            $headingsData[] = array(
                "MerchantTrackingId" => $value["MerchantTrackingId"],
                "CreditProspectCreated" => $value["CreditProspectCreated"],
                "CreditAppCreated" => $value["CreditAppCreated"], 
                "TimetoCreate" => $value["TimetoCreate"],
                "PartialDOB" => $value["PartialDOB"],
                "PartialPAN" => $value["PartialPAN"],
                "MaskedEmail" => $value["MaskedEmail"],
                "Email" => $value["Email"],
                "PartialMobilePhoneNumber" => $value["PartialMobilePhoneNumber"],
                "MobilePhoneNumber" => $value["MobilePhoneNumber"],
                "FirstName" => $value["FirstName"],
                "LastName" => $value["LastName"],
                "City" => $value["City"],
                "StateProv" => $value["StateProv"],
                "PostalCode" => $value["PostalCode"],
                "EmploymentStatusCode" => $value["EmploymentStatusCode"],
                "MarketingConsent" => $value["MarketingConsent"],
                "AllowEmail" => $value["AllowEmail"],
                "AllowSms" => $value["AllowSms"],
                "CreditAppReadings" => $value["CreditAppReadings"],
                "CreditProspectReadings" => $value["CreditProspectReadings"],
                "WhenModified" => $value["WhenModified"],
                "Attempts" => $value["Attempts"],
                "WhenLastAttempted" => $value["WhenLastAttempted"],
                "MonthlyIncome" => $value["MonthlyIncome"],
                "Submissions" => $value["Submissions"],
                "KnockOutLenders" => $value["KnockOutLenders"],
                "MoneyviewId" => $value["MoneyviewId"],
                "UpwardsId" => $value["UpwardsId"],
                "CasheId" => $value["CasheId"]
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
        $title = "Main";
        return $title; 
    }
}
