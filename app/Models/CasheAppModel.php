<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CreditApp;

class CasheAppModel extends Model
{
    use HasFactory;
    protected $table = "cashe_app";
    protected $primaryKey = "cashe_id";
    public $timestamps = true;

    public function cacheOffers($request){
        $creditAppData = CreditApp::where('creditapp_uuid',$request->creditapp_uid)->first();
        $this->checkDuplicateOfferLead($creditAppData['mobile_phone_number'],$creditAppData['birthdate']);

        
        
    }

    public function checkDuplicateOfferLead($mobileno, $birthdate){
        $cachePartnerName = config('constants.cachePartnerName');
        $cacheBaseUrl = config('constants.cacheApiBaseUrl');
        $bDate = date("d-m-Y",strtotime(date($birthdate)));
        $params = array(
            'partner_name' => $cachePartnerName,
            'last_five_digits_of_mobile' => substr($mobileno,5),
            'date_of_birth' => $bDate,
        );
        $appendTo = "checkDuplicateCustomerLead";
        $url = $cacheBaseUrl.$appendTo;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($status != 200) {
            die("Error: call to token URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
        }
        curl_close($curl);
        $response = json_decode($json_response, true);
       
        return $response;
    }
}
