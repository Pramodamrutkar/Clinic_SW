<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\CreditApp;
class UpwardsAppModel extends Model
{
    use HasFactory;

    protected $table = "upwards_app";
    protected $primaryKey = "upwardapp_id";
    public $timestamps = true;

    public function saveUpwardsDetails($request,$app_id){
        $creditAppIdCount = CreditApp::where('creditapp_uuid',$app_id)->count();
        if($creditAppIdCount != 1){
            return Response([
                'status' => 'fail',
                'message' => 'Invalid AppID'
            ],400);
        }
        $this->creditapp_uid = trim($app_id); 
        $this->upwardapp_uid = (string) Str::uuid(); 
        $this->residency_type = $request['residency_type'];
        $this->gender = $request['gender'];
        $this->current_residency_stay_category = $request['current_residency_stay_category'];
        $this->company = $request['company'];
        $this->salary_payment_mode = $request['salary_payment_mode'];
        $this->profession_type = $request['profession_type'];
        $this->current_employment_tenure = $request['current_employment_tenure'];
        $this->total_work_experience = $request['total_work_experience'];
        $this->bank_account_number = $request['bank_account_number'];
        $this->bank_account_holder_name = $request['bank_account_holder_name'];
        $this->ifsc = $request['ifsc'];
        $this->loan_purpose = $request['loan_purpose'];
        $this->lender_customer_id = $request['lender_customer_id'];
        $this->lender_system_id = $request['lender_system_id'];
        $this->Iframe_url = $request['Iframe_url'];
        $this->amount = $request['amount'];
        $this->emi = $request['emi'];
        $this->annual_interest_rate = $request['annual_interest_rate'];
        $this->term_months = $request['term_months'];
        //$this->mis_status = $request['mis_status'];
        $this->merchant_tracking_id = $request['merchant_tracking_id'];
        $this->leder_created = $request['leder_created'];
        $this->processing_fees = $request['processing_fees'];

        $this->getandStoreUpwardsInfo($this);
        if($this->save()){
            return Response([
                'status' => 'true',
                'message' => 'saved data successfully!',
                'upwardapp_uid' => $this->upwardapp_uid
            ],200);
        }else{
            return Response([
                'status' => 'false',
                'message' => 'Something went wrong'
            ],400);
        }
    }


    public function checkUpwardsEligibility($email,$pan){
        //$pan = trim($request->pan);
        //$email = trim($request->social_email_id);
        $params = array(
            "pan" => $pan,
            "social_email_id" => $email
        );
        $upwardTokenData = $this->getUpwardAccessToken();
        $accessToken = $upwardTokenData['data']['affiliated_user_session_token'];
        $affiliated_user_id = $upwardTokenData['data']['affiliated_user_id'];
                
        $upwardApiBaseUrl = config('constants.upwardApiBaseUrl');
        $appendTo = "v1/customer/loan/eligibility/";
        $url = $upwardApiBaseUrl.$appendTo;
        $curl = curl_init();
        $string = json_encode($params);
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $string,
            CURLOPT_HTTPHEADER => array(
              "Affiliated-User-Id: $affiliated_user_id",
              "Affiliated-User-Session-Token: $accessToken",
              "Content-Type: application/json"
            ),
          ));
          $json_response = curl_exec($curl);
          $response = json_decode($json_response, true);
          curl_close($curl);
          return $response;  
    } 
    
    public function getUpwardAccessToken(){
        $AffiliatedUserId = config('constants.upwardAffiliatedUserId');
        $Secret = config('constants.upwardSecret');
        $params = array(
            'affiliated_user_id' => $AffiliatedUserId,
            'affiliated_user_secret' => $Secret
        );
        $upwardApiBaseUrl = config('constants.upwardApiBaseUrl');
        $appendTo = "v1/authenticate/";
        $url = $upwardApiBaseUrl.$appendTo;
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

    public function getandStoreUpwardsInfo($data){
        $creditAppData = CreditApp::where("creditapp_uuid",$data['creditapp_uid'])->first();
       // print_r($data);
       
        $params = array(
            "first_name" => $creditAppData["first_name"],
            "last_name" => $creditAppData["last_name"],
            "is_partial_data" => false,
            "pan" => $creditAppData["tin"],
            "gender" => $creditAppData["gender"],
            "dob" => $creditAppData["birth_date"],
            "social_email_id" => $creditAppData["last_name"],
            "work_email_id" => $creditAppData["last_name"],
            "mobile_number1" => $creditAppData["last_name"],
            "company" => $creditAppData["last_name"],
            "employment_status_id" => $creditAppData["last_name"],
            "salary_payment_mode_id" => $creditAppData["last_name"],
            "profession_type_id" => $creditAppData["last_name"],
            "total_work_experience_category_id" => $creditAppData["last_name"],
            "salary" => $creditAppData["last_name"],
            "bank_account_number" => $creditAppData["last_name"],
            "bank_account_holder_full_name" => $creditAppData["last_name"],
            "ifsc" => $creditAppData["last_name"],
            "current_residence_type_id" => $creditAppData["last_name"],
            "current_address_line1" => $creditAppData["last_name"],
            "current_address_line2" => $creditAppData["last_name"],
            "current_pincode" => $creditAppData["last_name"],
            "current_city" => $creditAppData["last_name"],
            "current_state" => $creditAppData["last_name"],
            "current_residence_stay_category_id" => $creditAppData["last_name"],
            "loan_purpose_id" => $creditAppData["last_name"],
            "current_employment_tenure_category_id" => $creditAppData["last_name"]
        );

        $upwardTokenData = $this->getUpwardAccessToken();
        $accessToken = $upwardTokenData['data']['affiliated_user_session_token'];
        $affiliated_user_id = $upwardTokenData['data']['affiliated_user_id'];
                
        $upwardApiBaseUrl = config('constants.upwardApiBaseUrl');
        $appendTo = "v1/customer/loan/data/";
        $url = $upwardApiBaseUrl.$appendTo;
        $curl = curl_init();
        $string = json_encode($params);
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $string,
            CURLOPT_HTTPHEADER => array(
              "Affiliated-User-Id: $affiliated_user_id",
              "Affiliated-User-Session-Token: $accessToken",
              "Content-Type: application/json"
            ),
          ));
          $json_response = curl_exec($curl);
          $response = json_decode($json_response, true);
          curl_close($curl);
          return $response;  
    }
    
}
