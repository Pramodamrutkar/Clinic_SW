<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CreditApp;
use Mail;

class CasheAppModel extends Model
{
    use HasFactory;
    protected $table = "cashe_app";
    protected $primaryKey = "cashe_id";
    public $timestamps = true;


    public function casheOffers($mobile_phone_number,$birth_date,$monthly_income){
        // $creditAppData = CreditApp::where('creditapp_uuid',$request->creditapp_uid)->first();
        // if(empty($creditAppData)){
        //     return response([
        //         'success' => 'false',
        //         'message' => 'Invalid app ID No data found'
        //     ],400);
        // }
        $responseduplicateStatus = $this->checkDuplicateOfferLead($mobile_phone_number, $birth_date);
         if($responseduplicateStatus["statusCode"] == 200){
             if($responseduplicateStatus["duplicateStatusCode"] == 1){
                $offerResponse = $this->getPlans($monthly_income);
                $offers = $this->processCasheOffers($offerResponse);
                $cacheOffer = $this->getCasheRankWeightageOffer($offers);
                return $cacheOffer;
             }
         }else{
              return 0;
         }
    }

    public function checkDuplicateOfferLead($mobileno, $birthdate){
       
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
            $offers[$key]['lender_name'] = "CASHe";
            $offers[$key]['LenderId'] = "twiyp8jcr-aimx-ivuw-736d-90ud1f8lp6";
            $offers[$key]['LenderUrl'] = "https://www.cashe.co.in/"; 
            $offers[$key]['loanType'] = $value->loanType;
            $offers[$key]['offer_amount'] = $value->maxLoanEligibilityAmount;
            $offers[$key]['minLoanEligibilityAmount'] = $value->minLoanEligibilityAmount; 
            $offers[$key]['offer_roi'] = $value->interestRate;
            $offers[$key]['offer_pf'] = $value->processingFee;
            $offers[$key]['offer_tenure'] = $value->noOfInstallments;
            $offers[$key]['upfrontInterestDeductionPercentage'] = $value->upfrontInterestDeductionPercentage;
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
        return $response;
    }

    public function getCacheStatus($lender_system_id){
        $cachePartnerName = config('constants.cachePartnerName');
        $cacheBaseUrl = config('constants.cacheApiBaseUrl');
        
        $payload = array(
            'partner_name' => $cachePartnerName,
            'partner_customer_id' => $lender_system_id
        );
      
        $str = json_encode($payload);
        $passphrase = config('constants.passphrase');
        $checkSum = $this->generateCheckSum($str,$passphrase);

        $appendTo = "customer_status";
        $url = $cacheBaseUrl.$appendTo;
        $headersArray = array(
            "Check-Sum: $checkSum",
            "Content-Type: application/json"
        );
     
        $upwardModel = new UpwardsAppModel();
        $response = $upwardModel->curlCommonFunction($url,$payload,$headersArray); 
        return $response;
    }
    
    public function getCasheRankWeightageOffer($offers){
    
       $amountRanking = array_column($offers, "offer_amount");
       $roiRanking = array_column($offers, "offer_roi");
       $installmentRanking = array_column($offers, "offer_tenure");
       arsort($amountRanking); //higher the better
       asort($roiRanking); //lower the better
       arsort($installmentRanking); //higher the better
       $keyFromoffers = array_keys($amountRanking)[0];
       $cacheOffer = $offers[$keyFromoffers];
       return $cacheOffer;
    }

    public static function sendTemplateEmails($messagePage, $data, $attachment = ''){
        Mail::send($messagePage, $data, function ($message) use ($data) {
            $message->to($data['toEmail'])->subject($data['subject']);
            $message->from('noreply@creditlinks.in', 'CreditLinks');
            if (!empty($data['attachment'])) {
                $message->attach($data['attachment']);
            }
        });
    }

    public function sendCasheDownloadLink($app_id){
        $creditAppData = CreditApp::where("creditapp_uuid",trim($app_id))->first();
        if(empty($creditAppData)){
            return response([
                'success' => 'false',
                'message' => 'Invalid App ID'
            ],400);
        }
        $email = "";
        if(!empty($creditAppData["email"])){
            $email = $creditAppData["email"];
        }else{
            return response([
                'success' => 'false',
                'message' => 'No email exist'
            ],400);
        }
        $casheDownloadUrl = config('constants.cacheDownloadUrl');
        $subject = "CASHe mobile app download information"; 
        $firstName = $creditAppData["first_name"];
        $data = array("toEmail" => $email, "subject" => $subject, "firstName" => $firstName,"casheDownloadUrl" => $casheDownloadUrl);
        $messagePage = "cashetemplate";
        self::sendTemplateEmails($messagePage,$data);
        return response([
            'success' => 'true',
            'message' => 'Download Link has been sent successfully'
        ],200);
    }

}
