<?php

namespace App\Models;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\CreditApp;
use App\Models\SmartList;
use Exception;

class UpwardsAppModel extends Model
{
    use HasFactory;
    protected $fillable = [];
    protected $table = "upwards_app";
    protected $primaryKey = "upwardapp_id";
    public $timestamps = true;

    public function saveUpwardsDetails($request,$app_id){
        try{
            $upwardIframeUrl = '';
            $creditAppIdCount = CreditApp::where('creditapp_uuid',$app_id)->count();
            if($creditAppIdCount != 1){
                return Response([
                    'status' => 'fail',
                    'message' => 'Invalid AppID'
                ],400);
            }
            $upwardUpdatedata = UpwardsAppModel::where('creditapp_uid',$app_id)->first();
            if(empty($upwardUpdatedata)){
                return Response([
                    'status' => 'fail',
                    'message' => 'No Applied loan exist'
                ],400);
            }
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
                $upwardStatus = $this->getUpwardStatus($lenderSystemId,$lenderCustomerId);
                $upwardUpdatedata->mis_status = $upwardStatus;
                $upwardUpdatedata->lender_customer_id = $lenderCustomerId;
                $upwardUpdatedata->lender_system_id = $lenderSystemId;
                $upwardUpdatedata->Iframe_url = $upwardIframeUrl;
            }else{
                $upwardUpdatedata->lender_customer_id = $request['lender_customer_id'];
                $upwardUpdatedata->lender_system_id = $request['lender_system_id'];
                $upwardUpdatedata->Iframe_url = $request['Iframe_url'];
            }
            if($upwardUpdatedata->save()){
				$additionalData = $this->buildArrayForUpwardsToSFDC($request, $upwardUpdatedata);
				$casheAppModelObj = new CasheAppModel();
                $casheAppModelObj->storeAdditionalDataInSFDC($additionalData);
                return Response([
                    'status' => 'true',
                    'message' => 'saved data successfully!',
                    'upwardIframeUrl' => $upwardIframeUrl
                ],200);
            }else{
                return Response([
                    'status' => 'false',
                    'message' => 'Something went wrong'
                ],400);
            }
        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message, $app_id);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message, $app_id);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }

    }


    public function checkUpwardsEligibility($email,$pan){
        //$pan = trim($request->pan);
        //$email = trim($request->social_email_id);
        try{
            $payload = array(
                "pan" => $pan,
                "social_email_id" => $email
            );
            $upwardTokenData = $this->getUpwardAccessToken();
            $accessToken = $upwardTokenData['data']['affiliated_user_session_token'];
            $affiliated_user_id = $upwardTokenData['data']['affiliated_user_id'];

            $upwardApiBaseUrl = config('constants.upwardApiBaseUrl');
            $appendTo = "v1/customer/loan/eligibility/";
            $url = $upwardApiBaseUrl.$appendTo;

            $headersArray = array(
                "Affiliated-User-Id: $affiliated_user_id",
                "Affiliated-User-Session-Token: $accessToken",
                "Content-Type: application/json"
            );
            $response = $this->curlCommonFunction($url,$payload,$headersArray);
            return $response;
        }catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }
    }

    public function getUpwardAccessToken(){
        try{
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
            curl_close($curl);
            $response = json_decode($json_response, true);
            return $response;
        }catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }

    }

    public function getandStoreUpwardsInfo($data){
        try{
            $creditAppData = CreditApp::where("creditapp_uuid",$data['creditapp_uid'])->first();
            $payload = array(
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
                "employment_status_id" =>  SmartList::getFieldDescription($creditAppData["employment_status_code"]) == "Wrkr" ? 3 : 2,
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

            $headersArray = array(
                "Affiliated-User-Id: $affiliated_user_id",
                "Affiliated-User-Session-Token: $accessToken",
                "Content-Type: application/json"
            );
            $response = $this->curlCommonFunction($url, $payload, $headersArray);
            return $response;
        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message, $data['creditapp_uid']);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message, $data['creditapp_uid']);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }
    }

    public function registerUpwardsUrl($lenderCustomerId){
        $upwardIframeBaseUrl = config('constants.upwardIframeBaseUrl');
        $upwardAffiliatedUserId = config('constants.upwardAffiliatedUserId');
        $upwardTokenData = $this->getUpwardAccessToken();
        $accessToken = $upwardTokenData['data']['affiliated_user_session_token'];
        date_default_timezone_set("Asia/Kolkata");
        $nowTime = date("Y-m-d\TH:i:s");
        $concatedString = $accessToken.$nowTime;
        $affiliate_hash = md5($concatedString);
        $strUrl = $upwardIframeBaseUrl."customer_id=".$lenderCustomerId."&affiliate_user_id=".$upwardAffiliatedUserId."&hash_generation_datetime=".$nowTime."&affiliate_hash=".$affiliate_hash;
        return $strUrl;
    }

    public function getUpwardStatus($lenderSystemId,$lenderCustomerId){

        //$lenderSystemId = $request['lender_system_id'];
        //$lenderCustomerId = $request["lender_customer_id"];
        $upwardStatusData = $this->GetLoanApplicationStageAsync($lenderSystemId,$lenderCustomerId);

        if(!empty($upwardStatusData['data'])){
            $lenderStatus = $upwardStatusData['data']['loan_stage'] ?? "";
            if($lenderStatus != ""){
                $lapStatus = OfferStatusModel::getLapStatus("Upwards",$lenderStatus);
                return $lapStatus;
            }else{
                return "";
            }
        }else{
            return "";
        }
    }

    public function GetLoanApplicationStageAsync($lenderSystemId, $lenderCustomerId){
        try{
            $payload = array(
                "loan_id" => $lenderSystemId,
                "customer_id" => $lenderCustomerId,
                "level_id" => 1
            );
            $upwardApiBaseUrl = config('constants.upwardApiBaseUrl');
            $appendTo = "v1/customer/loan/stage/data/";
            $url = $upwardApiBaseUrl.$appendTo;

            $upwardTokenData = $this->getUpwardAccessToken();
            $accessToken = $upwardTokenData['data']['affiliated_user_session_token'];
            $affiliated_user_id = $upwardTokenData['data']['affiliated_user_id'];
            $headersArray = array(
                "Affiliated-User-Id: $affiliated_user_id",
                "Affiliated-User-Session-Token: $accessToken",
                "Content-Type: application/json"
            );
            $response = $this->curlCommonFunction($url,$payload,$headersArray);
            return $response;
        }catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }
    }

    /**
     * common function to initiate loan for all lenders based on lender name.
    */
    public function initiateLoanApplication($request){
        $app_id = $request['creditapp_id'];
        try{
            $creditAppIdCount = CreditApp::where('creditapp_uuid',$app_id)->count();
            if($creditAppIdCount != 1){
                return Response([
                    'status' => 'fail',
                    'message' => 'Invalid AppID'
                ],400);
            }
            if($request['lender_name'] == "Upwards"){
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

                $moneyViewObj->residency_type = $request['residency_type'] ?? "";
                $moneyViewObj->gender = $request['gender'] ?? "";
                $moneyViewObj->educational_level = $request['educational_level'] ?? "";
                $moneyViewObj->salary_payment_mode = $request['salary_payment_mode'] ?? "";
                $moneyViewObj->prefer_net_banking = $request['prefer_net_banking'] ?? 0;
                $moneyViewObj->term_of_use = $request['term_of_use'] ?? 0;
                $moneyViewObj->lender_system_id = $request['lender_system_id'] ?? "";
                $moneyViewObj->journey_url = $request['journey_url'] ?? "";
                $moneyViewObj->emi = $request['emi'] ?? 0;

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
            }else if($request['lender_name'] == "CASHe"){

                $casheLenderSystemId = 0;
                $statusOnOff = ExternalConnectorsModel::externalConnects("CREATECASHEUSER");
                if($statusOnOff == 1){
                    $cacheAppModel = new CasheAppModel();
                    $casheNewUserData = $cacheAppModel->createUserWithCache(trim($app_id));
                    if(!empty($casheNewUserData)){
                        if($casheNewUserData["statusCode"] == 200){
                            $casheLenderSystemId = $casheNewUserData["payLoad"];
                        }
                    }
                }
                $casheObj = new CasheAppModel();
                $casheObj->creditapp_uid = trim($app_id);
                $casheObj->cashe_uid = (string) Str::uuid();
                $casheObj->amount = $request['amount'];
                $casheObj->annual_interest_rate = $request['annual_interest_rate'];
                $casheObj->term_months = $request['term_months'];
                $casheObj->processing_fees = $request['processing_fees'];
                $casheObj->lender_system_id = $casheLenderSystemId ?? 0;
                $casheObj->mis_status = "Initiated";
                if($casheObj->save()){
                    return Response([
                        'status' => 'true',
                        'message' => 'saved data successfully!',
                        'cashe_uid' => $casheObj->creditapp_uid
                    ],200);
                }else{
                    return Response([
                        'status' => 'false',
                        'message' => 'Something went wrong'
                    ],400);
                }
            }else if($request['lender_name'] == "MoneyTap"){
                    $moneyTap = new MoneyTapModel();
                    $moneyTap->creditapp_uid = trim($app_id);

                    $moneyTap->amount = $request['amount'];
                    $moneyTap->annual_interest_rate = $request['annual_interest_rate'];
                    $moneyTap->term_months = $request['term_months'];
                    $moneyTap->processing_fees = $request['processing_fees'];
                    $moneyTap->mis_status = "Initiated";
                    if($moneyTap->save()){
                        return Response([
                            'status' => 'true',
                            'message' => 'saved data successfully!',
                            'moneyview_uid' => $moneyTap->moneytap_id
                        ],200);
                    }else{
                        return Response([
                            'status' => 'false',
                            'message' => 'Something went wrong'
                        ],400);
                    }
            }else{
                return Response([
                    'status' => 'false',
                    'message' => 'Invalid Lender Name'
                ],400);

            }

        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message, $app_id);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message, $app_id);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }

    }

    public function getUpwardsOthersOffer($request,$id){
        try{
            $token = $request->header('token');
            if(empty($token)) {
                return response([
                    'success' => 'false',
                    'message' => 'No token found'
                ], 400);
            }
            $tokenCnt = PersonalAccessToken::checkTokenExpire(trim($token),"1001");
            if($tokenCnt == 1){
                $upwardsApp = UpwardsAppModel::select('upwards_app.lender_name as Lender', 'upwards_app.lender_system_id as LenderId', 'upwards_app.processing_fees as FeesInfo','upwards_app.annual_interest_rate as RateInfo','upwards_app.emi as Emi','upwards_app.amount as Amount','upwards_app.term_months as TermMonths','upwards_app.loan_purpose as LoanType')->where('creditapp_uid',$id)->first();
                $moneyView = MoneyViewAppModel::select('moneyview_app.lender_name as Lender', 'moneyview_app.lender_system_id as LenderId', 'moneyview_app.processing_fees as FeesInfo','moneyview_app.annual_interest_rate as RateInfo','moneyview_app.emi as Emi','moneyview_app.amount as Amount','moneyview_app.term_months as TermMonths')->where('creditapp_uid',$id)->first();
                $casheApp = CasheAppModel::select('cashe_app.lender_name as Lender', 'cashe_app.lender_system_id as LenderId', 'cashe_app.processing_fees as FeesInfo','cashe_app.annual_interest_rate as RateInfo','cashe_app.emi as Emi','cashe_app.amount as Amount','cashe_app.term_months as TermMonths')->where('creditapp_uid',$id)->first();
                $offerData = array();
                if(!empty($upwardsApp)){
                    $offerData[] = $upwardsApp;
                }
                if(!empty($moneyView)){
                    $offerData[] = $moneyView;
                }
                if(!empty($casheApp)){
                    $offerData[] = $casheApp;
                }
                $records = array("Offers" => $offerData);
                return $records;
            }else{
                return response([
                    'success' => 'false',
                    'message' => 'Invalid token'
                ], 400);
           }
        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message, $id);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message, $id);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }
    }

    /**
     * common CURL function to call lenders APIs.
     */
    public function curlCommonFunction($url,$payload,$headersArray){
        $curl = curl_init();
        $string = json_encode($payload);

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
            CURLOPT_HTTPHEADER => $headersArray,
          ));
        $json_response = curl_exec($curl);
        $response = json_decode($json_response, true);
        curl_close($curl);
        return $response;
    }

	public function buildArrayForUpwardsToSFDC($request, $upwardUpdatedata)
	{
        $creditAppData = CreditApp::where("creditapp_uuid", $upwardUpdatedata->creditapp_uid)->first();
		$upwardSFDC['Id'] = $upwardUpdatedata->creditapp_uid;
		$upwardSFDC['Gender'] = SmartList::getFieldDescription($request['gender']);
		$upwardSFDC['LenderName'] = $upwardUpdatedata->lender_name;
		$upwardSFDC['Company'] = strval($request['company']);
		$upwardSFDC['EmploymentStatus'] = SmartList::getFieldDescription($creditAppData["employment_status_code"]) == "Wrkr" ? 3 : 2;
		$upwardSFDC['SalaryPaymentMode'] = intval(SmartList::getFieldDescription($request['salary_payment_mode']));
		$upwardSFDC['ProfessionType'] = intval(SmartList::getFieldDescription($request['profession_type']));
		$upwardSFDC['TotalWorkExperience'] = intval($request['total_work_experience']);
		$upwardSFDC['BankAccountHolderFullName'] = $request['bank_account_holder_name'];
		$upwardSFDC['BankAccountNumber'] = strval($upwardUpdatedata->bank_account_number);
		$upwardSFDC['IFSC'] = strval($request['ifsc']);
		$upwardSFDC['ResidencyType'] = intval(SmartList::getFieldDescription($request['residency_type']));
		$upwardSFDC['CurrentResidencyStayCategory'] = intval(SmartList::getFieldDescription($request['current_residency_stay_category']));
		$upwardSFDC['LoanPurpose'] = intval(SmartList::getFieldDescription($request['loan_purpose']));
		$upwardSFDC['CurrentEmploymentTenure'] = intval(SmartList::getFieldDescription($request['current_employment_tenure']));
		$upwardSFDC['Created'] = $upwardUpdatedata->created_at;
		$upwardSFDC['MisStatus'] = $upwardUpdatedata->mis_status;
		$upwardSFDC['Updated'] = $upwardUpdatedata->updated_at;
		$upwardSFDC['LenderSystemId'] = !empty($upwardUpdatedata->lender_system_id) ? strval($upwardUpdatedata->lender_system_id) : "4574779";
		$upwardSFDC['LenderCustomerId'] = !empty($upwardUpdatedata->lender_customer_id) ? strval($upwardUpdatedata->lender_customer_id) : "4574779";
		$upwardSFDC['MerchantLocationId'] = strval($request['merchant_tracking_id']);
		$upwardSFDC['SelectedOffer']['EMI'] = $upwardUpdatedata->emi;
		$upwardSFDC['SelectedOffer']['Amount'] = $upwardUpdatedata->amount;
		$upwardSFDC['SelectedOffer']['TermsMonth'] = $upwardUpdatedata->term_months;
		$upwardSFDC['SelectedOffer']['Fees'] = $upwardUpdatedata->processing_fees;
		$upwardSFDC['SelectedOffer']['Created'] = $upwardUpdatedata->created_at;
		$upwardSFDC['SelectedOffer']['Rate'] = $upwardUpdatedata->annual_interest_rate;
		return $upwardSFDC;
	}

}
