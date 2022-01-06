<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use DB;

class InternalReport implements FromCollection
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
}
