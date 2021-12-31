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
      
        $responseduplicateStatus = $this->checkDuplicateOfferLead($creditAppData['mobile_phone_number'], $creditAppData['birth_date'],$creditAppData);
        if($responseduplicateStatus["statusCode"] == 200){
            if($responseduplicateStatus["duplicateStatusCode"] == 1){
                $offerResponse = $this->getPlans($creditAppData->monthly_income);
                $offers = $this->processCasheOffers($offerResponse);
                return $offers;
            }
        }else{
             return 0;
        }
    }

    public function checkDuplicateOfferLead($mobileno, $birthdate,$creditAppData){
       
        $cachePartnerName = config('constants.cachePartnerName');
        $cacheBaseUrl = config('constants.cacheApiBaseUrl');
        $bDate =  date("d-m-Y", strtotime($birthdate));  
        
        $payload = array(
            'partner_name' => $cachePartnerName,
            'last_five_digits_of_mobile' => substr($mobileno,5),
            'date_of_birth' => $bDate
        );
      
        $str = json_encode($payload);
        $passphrase = config('constants.passphrase');
    
        $checkSum = $this->generateCheckSum($str,$passphrase);

        $appendTo = "checkDuplicateCustomerLead";
        $url = $cacheBaseUrl.$appendTo;
        $headersArray = array(
            "Check-Sum: $checkSum",
            "Content-Type: application/json"
        );
        $upwardModel = new UpwardsAppModel();
        $response = $upwardModel->curlCommonFunction($url,$payload,$headersArray); 
        return $response;
    }

    public function generateCheckSum($data, $key){
        $hmac = hash_hmac("sha1", $data, $key, TRUE);
        $signature = base64_encode($hmac);
        return $signature;
    }

    public function getPlans($salary){
        $cachePartnerName = config('constants.cachePartnerName');
        $cacheBaseUrl = config('constants.cacheApiBaseUrl');
        
        $payload = array(
            'partner_name' => $cachePartnerName,
            'salary' => $salary
        );
      
        $str = json_encode($payload);
        $passphrase = config('constants.passphrase');
        $checkSum = $this->generateCheckSum($str,$passphrase);

        $appendTo = "fetchCashePlans/salary";
        $url = $cacheBaseUrl.$appendTo;
        $headersArray = array(
            "Check-Sum: $checkSum",
            "Content-Type: application/json"
        );

        $upwardModel = new UpwardsAppModel();
        $response = $upwardModel->curlCommonFunction($url,$payload,$headersArray); 
        return $response;
    }

    public function processCasheOffers($offerResponse){
        $paylodData = json_decode($offerResponse["payLoad"]);
        $offers = array();
        foreach ($paylodData as $key => $value) {
            $offers[$key]['Lender'] = "CASHe";
            $offers[$key]['LenderId'] = "twiyp8jcr-aimx-ivuw-736d-90ud1f8lp6";
            $offers[$key]['LenderUrl'] = "https://www.cashe.co.in/"; 
            $offers[$key]['loanType'] = $value->loanType;
            $offers[$key]['maxLoanEligibilityAmount'] = $value->maxLoanEligibilityAmount;
            $offers[$key]['minLoanEligibilityAmount'] = $value->minLoanEligibilityAmount; 
            $offers[$key]['interestRate'] = $value->interestRate;
            $offers[$key]['noOfInstallments'] = $value->loanType;
            $offers[$key]['loanType'] = $value->loanType;
            $offers[$key]['upfrontInterestDeductionPercentage'] = $value->loanType;
        }
        return $offers;
    }

    public function createUserWithCache($app_id){
        $creditAppData = CreditApp::where("creditapp_uuid",trim($app_id))->first();
       
        if(empty($creditAppData)){
            return Response([
                'status' => 'false',
                'message' => 'Invalid Application ID for CASHe'
            ],400);
        }
        
        $cachePartnerName = config('constants.cachePartnerName');
        $passphrase = config('constants.passphrase');
        $cacheBaseUrl = config('constants.cacheApiBaseUrl');
        $requestUrl = "create_customer";
        
        $payload = array();
        $payload['partner_name'] = $cachePartnerName;
        $payload['Personal Information']['First Name'] = $creditAppData["first_name"];
        $payload['Personal Information']['Last Name'] = $creditAppData["last_name"];
        $payload['Personal Information']['DOB'] = date("d-m-Y",strtotime($creditAppData["birth_date"]));
        $payload['Personal Information']['Address Line1'] = $creditAppData["address1"];
        $payload['Personal Information']['Address Line2'] = $creditAppData["address1"];
        $payload['Personal Information']['Pincode'] = $creditAppData["postal_code"];
        $payload['Personal Information']['City'] = $creditAppData["city"];
        $payload['Personal Information']['State'] = $creditAppData["state"];
        $payload['Personal Information']['PAN'] = $creditAppData["tin"];

        $payload['Applicant Information']['Employment Type'] = SmartList::getFieldDescription($creditAppData["employment_status_code"]) == "Wrkr" ? "Salaried" : "Other";

        $payload['Contact Information']['Mobile'] = $creditAppData["mobile_phone_number"];
        $payload['Contact Information']['Email Id'] = $creditAppData["email"];
       
        $str = json_encode($payload);
    
        $checkSum = $this->generateCheckSum($str,$passphrase);
       
        $url = $cacheBaseUrl.$requestUrl;
        $headersArray = array(
            "Check-Sum: $checkSum",
            "Content-Type: application/json"
        );

        $upwardModel = new UpwardsAppModel();
        $response = $upwardModel->curlCommonFunction($url,$payload,$headersArray); 
        dd($response);
        return $response;
    }
    
}
