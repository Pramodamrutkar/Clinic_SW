<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\CreditApp;
use App\Models\SmartList;

class UpwardsAppModel extends Model
{
    use HasFactory;
    protected $fillable = [];
    protected $table = "upwards_app";
    protected $primaryKey = "upwardapp_id";
    public $timestamps = true;

    public function saveUpwardsDetails($request,$app_id){
        $upwardIframeUrl = '';
        $creditAppIdCount = CreditApp::where('creditapp_uuid',$app_id)->count();
        if($creditAppIdCount != 1){
            return Response([
                'status' => 'fail',
                'message' => 'Invalid AppID'
            ],400);
        }
        $upwardUpdatedata = UpwardsAppModel::where('creditapp_uid',$app_id)->first();
        $upwardUpdatedata->creditapp_uid = trim($app_id); 
        $upwardUpdatedata->residency_type = $request['residency_type'];
        $upwardUpdatedata->gender = $request['gender'];
        $upwardUpdatedata->current_residency_stay_category = $request['current_residency_stay_category'];
        $upwardUpdatedata->company = $request['company'];
        $upwardUpdatedata->salary_payment_mode = $request['salary_payment_mode'];
        $upwardUpdatedata->profession_type = $request['profession_type'];
        $upwardUpdatedata->current_employment_tenure = $request['current_employment_tenure'];
        $upwardUpdatedata->total_work_experience = $request['total_work_experience'];
        $upwardUpdatedata->bank_account_number = $request['bank_account_number'];
        $upwardUpdatedata->bank_account_holder_name = $request['bank_account_holder_name'];
        $upwardUpdatedata->ifsc = $request['ifsc'];
        $upwardUpdatedata->loan_purpose = $request['loan_purpose'];
        //$this->amount = $request['amount'];
        $upwardUpdatedata->emi = $request['emi'];
        //$this->annual_interest_rate = $request['annual_interest_rate'];
        //$this->term_months = $request['term_months'];
        //$this->mis_status = $request['mis_status'];
        $upwardUpdatedata->merchant_tracking_id = $request['merchant_tracking_id'];
        $upwardUpdatedata->lender_created = $request['lender_created'];
        //$this->processing_fees = $request['processing_fees'];
        $statusOnOff = ExternalConnectorsModel::externalConnects("CHECKUPWARDS");
       
        if($statusOnOff == 1){
            $result = $this->getandStoreUpwardsInfo($upwardUpdatedata);
            if(empty($result['data']['loan_data'])){
                return Response([
                    'status' => 'false',
                    'message' => 'Incorrect data',
                    'data' => $result['data']
                ],400);
            }
            $lenderCustomerId = $result["data"]["loan_data"]["customer_id"];
            $lenderSystemId = $result["data"]["loan_data"]["loan_id"];
            $upwardIframeUrl = $this->registerUpwardsUrl($lenderCustomerId);
            $upwardUpdatedata->lender_customer_id = $lenderCustomerId;
            $upwardUpdatedata->lender_system_id = $lenderSystemId; 
            $upwardUpdatedata->Iframe_url = $upwardIframeUrl;
        }else{
            $upwardUpdatedata->lender_customer_id = $request['lender_customer_id'];
            $upwardUpdatedata->lender_system_id = $request['lender_system_id'];
            $upwardUpdatedata->Iframe_url = $request['Iframe_url'];
        }   
        if($upwardUpdatedata->save()){
            return Response([
                'status' => 'true',
                'message' => 'saved data successfully!',
                'upwardapp_uid' => $upwardIframeUrl
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
 
        $params = array(
            "first_name" => $creditAppData["first_name"],
            "last_name" => $creditAppData["last_name"],
            "is_partial_data" => false,
            "pan" => $creditAppData["tin"],
            "gender" => SmartList::getFieldDescription($data["gender"]),
            "dob" => $creditAppData["birth_date"],
            "social_email_id" => $creditAppData["email"],
            "work_email_id" => $creditAppData["email"],
            "mobile_number1" => $creditAppData["mobile_phone_number"],
            "company" => $data["company"],
            "employment_status_id" =>  SmartList::getFieldDescription($creditAppData["employment_status_code"]),
            "salary_payment_mode_id" =>  SmartList::getFieldDescription($data["salary_payment_mode"]),
            "profession_type_id" => SmartList::getFieldDescription($data["profession_type"]),
            "total_work_experience_category_id" => SmartList::getFieldDescription($data["total_work_experience"]),
            "salary" => $creditAppData["monthly_income"],
            "bank_account_number" => $data["bank_account_number"],
            "bank_account_holder_full_name" => $data["bank_account_holder_name"],
            "ifsc" => $data["ifsc"],
            "current_residence_type_id" => SmartList::getFieldDescription($data["residency_type"]),
            "current_address_line1" => $creditAppData["address1"],
            "current_address_line2" => $creditAppData["address2"],
            "current_pincode" => $creditAppData["postal_code"],
            "current_city" => $creditAppData["city"],
            "current_state" => $creditAppData["state"],
            "current_residence_stay_category_id" => SmartList::getFieldDescription($data["current_residency_stay_category"]),
            "loan_purpose_id" => SmartList::getFieldDescription($data["loan_purpose"]),
            "current_employment_tenure_category_id" => SmartList::getFieldDescription($data["current_employment_tenure"])
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
    
    public function registerUpwardsUrl($lenderCustomerId){
        $upwardIframeBaseUrl = config('constants.upwardIframeBaseUrl');
        $upwardAffiliatedUserId = config('constants.upwardAffiliatedUserId');
        $upwardTokenData = $this->getUpwardAccessToken();
        $accessToken = $upwardTokenData['data']['affiliated_user_session_token'];
        $nowTime = date("Y-m-d H:i:s");
        $strUrl = $upwardIframeBaseUrl."customer_id=".$lenderCustomerId."&affiliate_user_id=".$upwardAffiliatedUserId."&hash_generation_datetime=".$nowTime."&affiliate_hash=".$accessToken;
        return $strUrl;
    }

    public function getUpwardStatus($request){
        $lenderSystemId = $request['lender_system_id'];
        $lenderCustomerId = $request["lender_customer_id"];
        $updwardStatusArray = array( 
            "data_submit" => "Initiated",
            "document_submit" => "In progress",
            "initial_application_complete" => "Processing",
            "initial_sanctioned",
            "initial_pre_approved", 
            "initial_sanctioned_data_complete" => "Approved",
            "initial_disbursed" => "Disbursed",
            "inactive" => "Expired",
            "initial_post_sanction_dropout" => "Turned Down",
            "initial_pre_rejected" => "Declined"
        );

        $this->GetLoanApplicationStageAsync($lenderSystemId,$lenderCustomerId);

    }

    public function GetLoanApplicationStageAsync($lenderSystemId, $lenderCustomerId){
 
        $params = array(
            "loan_id" => $lenderSystemId,
            "customer_id" => $lenderCustomerId
        );
        $upwardApiBaseUrl = config('constants.upwardApiBaseUrl');
        $appendTo = "v1/customer/loan/stage/data/";
        $url = $upwardApiBaseUrl.$appendTo;
        $curl = curl_init();
       //$string = json_encode($params);
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
              ),
          ));
          $json_response = curl_exec($curl);
          $response = json_decode($json_response, true);
          curl_close($curl);
          dd($response);
          return $response;  
    }
    /**
     * common function to initiate loan for all lenders based on lender name.
     */
    public function initiateLoanApplication($request){
        $app_id = $request['creditapp_id']; 
        $creditAppIdCount = CreditApp::where('creditapp_uuid',$app_id)->count();
        if($creditAppIdCount != 1){
            return Response([
                'status' => 'fail',
                'message' => 'Invalid AppID'
            ],400);
        }
        if($request['lender_name'] = "Upward"){
            $this->creditapp_uid = trim($app_id); 
            $this->upwardapp_uid = (string) Str::uuid(); 
            $this->residency_type = $request['residency_type'] ?? "";
            $this->gender = $request['gender'] ?? "";
            $this->current_residency_stay_category = $request['current_residency_stay_category'] ?? "";
            $this->company = $request['company'] ?? "";
            $this->salary_payment_mode = $request['salary_payment_mode'] ?? ""; 
            $this->profession_type = $request['profession_type'] ?? "";
            $this->current_employment_tenure = $request['current_employment_tenure'] ?? 0;
            $this->total_work_experience = $request['total_work_experience'] ?? 0;
            $this->bank_account_number = $request['bank_account_number'] ?? "";
            $this->bank_account_holder_name = $request['bank_account_holder_name'] ?? "";
            $this->ifsc = $request['ifsc'] ?? "";
            $this->loan_purpose = $request['loan_purpose'] ?? "";
            
            $this->amount = $request['amount'];
            $this->annual_interest_rate = $request['annual_interest_rate'];
            $this->term_months = $request['term_months'];
            $this->processing_fees = $request['processing_fees'];
            $this->mis_status = "Initiated";
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
        }else if($request['lender_name'] == "MoneyView"){
            $moneyViewObj = new MoneyViewAppModel();
            $moneyViewObj->creditapp_uid = trim($app_id); 
            $moneyViewObj->moneyview_uid = (string) Str::uuid(); 
            $moneyViewObj->amount = $request['amount'];
            $moneyViewObj->annual_interest_rate = $request['annual_interest_rate'];
            $moneyViewObj->term_months = $request['term_months'];
            $moneyViewObj->processing_fees = $request['processing_fees'];
            $moneyViewObj->mis_status = "Initiated";
            if($moneyViewObj->save()){
                return Response([
                    'status' => 'true',
                    'message' => 'saved data successfully!',
                    'moneyview_uid' => $moneyViewObj->moneyview_uid
                ],200);
            }else{
                return Response([
                    'status' => 'false',
                    'message' => 'Something went wrong'
                ],400);
            }
        }else if($request['lender_name'] == "CashE"){
            $casheObj = new CasheAppModel();
            $casheObj->creditapp_uid = trim($app_id); 
            $casheObj->cashe_uid = (string) Str::uuid(); 
            $casheObj->amount = $request['amount'];
            $casheObj->annual_interest_rate = $request['annual_interest_rate'];
            $casheObj->term_months = $request['term_months'];
            $casheObj->processing_fees = $request['processing_fees'];
            $casheObj->mis_status = "Initiated";
            if($casheObj->save()){
                return Response([
                    'status' => 'true',
                    'message' => 'saved data successfully!',
                    'cashe_uid' => $casheObj->cashe_uid
                ],200);
            }else{
                return Response([
                    'status' => 'false',
                    'message' => 'Something went wrong'
                ],400);
            }
        }
        
    }

}
