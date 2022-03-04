<?php

namespace App\Models;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\CreditApp;
use App\Models\CasheAppModel;
use App\Models\UpwardsAppModel;
use App\Models\OfferStatusModel;
use App\Models\MoneyTapModel;
use DB;
use Exception;

class MoneyViewAppModel extends Model
{
    use HasFactory;

    protected $table = "moneyview_app";
    protected $primaryKey = "moneyview_id";
    public $timestamps = true;

    public function saveMoneyView($request,$app_id){
        try{
            $creditAppIdCount = CreditApp::where('creditapp_uuid',$app_id)->count();
            if($creditAppIdCount != 1){
                return Response([
                    'status' => 'fail',
                    'message' => 'Invalid AppID'
                ],400);
            }

            $moneyViewUpdateData = MoneyViewAppModel::where('creditapp_uid',$app_id)->first();

            if(empty($moneyViewUpdateData)){
                return Response([
                    'status' => 'fail',
                    'message' => 'No Applied loan exist'
                ],400);
            }
            //$moneyViewUpdateData->moneyview_uid = (string) Str::uuid();
            //$this->creditapp_uid = trim($app_id);
            $moneyViewUpdateData->residency_type = $request['residency_type'];
            $moneyViewUpdateData->gender = $request['gender'];
            $moneyViewUpdateData->educational_level = $request['educational_level'];
            $moneyViewUpdateData->salary_payment_mode = $request['salary_payment_mode'];
            $moneyViewUpdateData->prefer_net_banking = $request['prefer_net_banking'];
            $moneyViewUpdateData->term_of_use = $request['term_of_use'] ?? 1;
            $moneyViewUpdateData->emi = $request['emi'];
            $moneyViewUpdateData->merchant_tracking_id = $request['merchant_tracking_id'];
            $moneyViewUpdateData->lender_created = $request['lender_created'] ?? date("Y-m-d");

            $lenderSystemId = $this->mvCreateLead($app_id,$moneyViewUpdateData);
            $moneyViewUpdateData->lender_system_id = $lenderSystemId;
            $journeyUrl = $this->getJourneyUrl($lenderSystemId);
            $moneyViewUpdateData->journey_url = $journeyUrl ?? "";

            if($moneyViewUpdateData->save()){
				$moneyViewSFDC = $this->buildArrayForMoneyViewToSFDC($moneyViewUpdateData);
                $casheAppModelObj = new CasheAppModel();
				$casheAppModelObj->storeAdditionalDataInSFDC($moneyViewSFDC);
                return Response([
                    'status' => 'true',
                    'message' => 'saved data successfully!',
                    'mvIframeUrl' => $moneyViewUpdateData->journey_url
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
            ErrorLogModel::LogError($status = 500, $code, $message,$app_id);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message,$app_id);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }
    }

    public function listOfferChart($app_id){
        try{
            if(empty($app_id)){
                return Response([
                    'status' => 'false',
                    'message' => 'Empty Application ID'
                ],400);
            }
            $upwardlenderSystemId = '';
            $UpwardsAppModel = UpwardsAppModel::select('*')->where('creditapp_uid',$app_id)->first();

            if(!empty($UpwardsAppModel)){
                $upwardlenderSystemId = $UpwardsAppModel['lender_system_id'];
                $lenderCustomerId = $UpwardsAppModel['lender_customer_id'];
            }
            if(!empty($upwardlenderSystemId)){
                $upwardsApp = new UpwardsAppModel();
                $upwardStatus = $upwardsApp->getUpwardStatus($upwardlenderSystemId,$lenderCustomerId);
                //$upwardModel = UpwardsAppModel::where("creditapp_uid",$app_id)->first();
                $offerExpireArray = array("Expired","Declined","Disbursed");
                $UpwardsAppModel->mis_status = $upwardStatus ?? "";
                if(in_array($upwardStatus,$offerExpireArray)){
                    $UpwardsAppModel->offer_expire_at = date('Y:m:d H:i:s', strtotime('+45 days'));
                }
                $UpwardsAppModel->save();
            }

            $statusOnOff = ExternalConnectorsModel::externalConnects("CASHESTATUS");
            if($statusOnOff == 1){
                $cashelenderSystemId = 0;
                $casheAppModel = CasheAppModel::select('*')->where('creditapp_uid',$app_id)->first();

                if(!empty($casheAppModel)){
                    $cashelenderSystemId = $casheAppModel['lender_system_id'];
                    $casheApp = new CasheAppModel();
                    $responseCasheStatus = $casheApp->getCacheStatus($cashelenderSystemId);
                    $offerExpireArray = array("Error in request","Declined","Disbursed");
                    if(!empty($responseCasheStatus)){
                        $casheAppModel->mis_status = $responseCasheStatus;
                        if(in_array($responseCasheStatus,$offerExpireArray)){
                            $casheAppModel->offer_expire_at = date('Y:m:d H:i:s', strtotime('+45 days'));
                        }
                        $casheAppModel->save();
                    }

                }
            }

            $mvstatusOnOff = ExternalConnectorsModel::externalConnects("MVSTATUS");
            if($mvstatusOnOff == 1){
                $moneyViewUpdateData = MoneyViewAppModel::select('*')->where('creditapp_uid',$app_id)->first();
                if(!empty($moneyViewUpdateData)){
                    $mvLenderSystemId = $moneyViewUpdateData["lender_system_id"];
                    if(!empty($mvLenderSystemId)){
                        $offerExpireArray = array("Expired","Declined");
                        $status = $this->getMvStatus($mvLenderSystemId);
                        $moneyViewUpdateData->mis_status = $status ?? "Initiated";
                        if(in_array($status,$offerExpireArray)){
                            $moneyViewUpdateData->offer_expire_at = date('Y:m:d H:i:s', strtotime('+45 days'));
                        }
                        $moneyViewUpdateData->save();
                    }
                }
            }
            $mvstatusOnOff = ExternalConnectorsModel::externalConnects("MTSTATUS");
            if($mvstatusOnOff == 1){
                $moneyTapUpdateData = MoneyTapModel::select('*')->where('creditapp_uid',$app_id)->first();
                if(!empty($moneyTapUpdateData)){
                    $mtCustomerId = $moneyTapUpdateData["lender_customer_id"];
                    if(!empty($mtCustomerId)){
                        $offerExpireArray = array("Expired","Declined");
                        $moneyTapModel = new MoneyTapModel();
                        $status = $moneyTapModel->getMoneyTapLeadStatus($mtCustomerId);
                        $moneyTapUpdateData->mis_status = $status ?? "In-Progress";
                        if(in_array($status,$offerExpireArray)){
                            $moneyTapUpdateData->offer_expire_at = date('Y:m:d H:i:s', strtotime('+45 days'));
                        }
                        $moneyTapUpdateData->save();
                    }
                }
            }



            // $offerData = DB::table('moneyview_app')
            // ->leftJoin('upwards_app', 'moneyview_app.creditapp_uid', '=' ,'upwards_app.creditapp_uid')
            // ->leftJoin('cashe_app','cashe_app.creditapp_uid', '=', 'moneyview_app.creditapp_uid')
            // ->where('moneyview_app.creditapp_uid','=',trim($app_id))
            // ->select('moneyview_app.lender_system_id as lender','moneyview_app.amount as amount','moneyview_app.mis_status as status','upwards_app.lender_system_id as upwardsLender','upwards_app.amount as upwardsAmount','upwards_app.mis_status as upwardsStatus','cashe_app.lender_system_id as casheLender','cashe_app.amount as casheAmount','cashe_app.mis_status as casheStatus')
            // ->first();

            $upwardsApp = UpwardsAppModel::select('upwards_app.lender_name as lender','upwards_app.amount as amount','upwards_app.mis_status as status','upwards_app.Iframe_url as Iframe_url')->where('creditapp_uid',$app_id)->first();
            $moneyView = MoneyViewAppModel::select('moneyview_app.lender_name as lender','moneyview_app.amount as amount','moneyview_app.mis_status as status','moneyview_app.journey_url as Iframe_url')->where('creditapp_uid',$app_id)->first();
            $casheApp = CasheAppModel::select('cashe_app.lender_name as lender','cashe_app.amount as amount','cashe_app.mis_status as status')->where('creditapp_uid',$app_id)->first();
            $moneyTap = MoneyTapModel::select('money_tap.lender_name as lender','money_tap.amount as amount','money_tap.mis_status as status')->where('creditapp_uid',$app_id)->first();

            $offerData = array();
            if(!empty($upwardsApp)){
                $offerData[] = $upwardsApp;
            }
            if(!empty($moneyTap)){
                $offerData[] = $moneyTap;
            }
            if(!empty($moneyView)){
                $offerData[] = $moneyView;
            }
            if(!empty($casheApp)){
                $offerData[] = $casheApp;
            }

            if(!empty($offerData)){
                return Response([
                    'status' => 'true',
                    'message' => 'Data found',
                    'data' => $offerData
                ],200);
            }else{
                return Response([
                    'status' => 'false',
                    'message' => 'No Data'
                ],204);
            }

        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message,$app_id);
            //echo ErrorLogModel::genericMessage();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message,$app_id);
            //echo ErrorLogModel::genericMessage();
        }
    }

    public function getMoneyviewAccessToken(){
        try{
            $moneyviewPartnerCode = config('constants.moneyviewPartnerCode');
            $moneyviewUserName = config('constants.moneyviewUserName');
            $moneyviewPassword = config('constants.moneyviewPassword');
            $moneyViewBaseUrl = config('constants.moneyviewApiBaseUrl');

            $tokenUrl = "v1/token";
            $url = $moneyViewBaseUrl.$tokenUrl;

            $payload = array(
                "userName" => $moneyviewUserName,
                "password" => $moneyviewPassword,
                "partnerCode" => $moneyviewPartnerCode
            );
            $headersArray = array('Content-Type: application/json');
            $upwardAppModel = new UpwardsAppModel();
            $response = $upwardAppModel->curlCommonFunction($url, $payload, $headersArray);
            if(!empty($response)){
                if($response["status"] == "success"){
                    return $response["token"];
                }
            }else{
                $message = "Unable to get access token for Moneyview service";
                ErrorLogModel::LogError($status = 400, 400, $message);
                $errolog = new ErrorLogModel();
                return $errolog->genericMsg();
            }
        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }
    }

    public function mvCreateLead($appId,$mvData){
        try{
            $creditAppData = CreditApp::where("creditapp_uuid",$appId)->first();
            $moneyviewPartnerCode = config('constants.moneyviewPartnerCode');
            $moneyViewBaseUrl = config('constants.moneyviewApiBaseUrl');

            $createLead = "v1/lead";
            $url = $moneyViewBaseUrl.$createLead;

            $token = $this->getMoneyviewAccessToken();
            $payload = array();
            $payload["partnerCode"] = $moneyviewPartnerCode;
            $payload["partnerRef"] = $appId;
            $payload["phone"] = $creditAppData["mobile_phone_number"];
            $payload["pan"] =  $creditAppData["tin"];
            $payload["name"] = $creditAppData["first_name"]." ".$creditAppData["last_name"];
            $payload["gender"] = SmartList::getFieldDescription($mvData["gender"]);
            $payload["dateOfBirth"] = $creditAppData["birth_date"];
            $payload["bureauPermission"] = $mvData["term_of_use"];
            $payload["declaredIncome"] = $creditAppData["monthly_income"];
            $payload["educationLevel"] = SmartList::getFieldDescription($mvData["educational_level"]);
            $payload["employmentType"] = SmartList::getFieldDescription($creditAppData["employment_status_code"]) == "Wrkr" ? "Salaried" : "Self Employed";
            $payload["incomeMode"] = SmartList::getFieldDescription($mvData["salary_payment_mode"]);
            $payload["preferNetBanking"] = $mvData["prefer_net_banking"] ? true : false;
            $addressData = [];
            $addressData["addressLine1"] = $creditAppData["address1"];
            $addressData["addressLine2"] = $creditAppData["address2"];
            $addressData["city"] = $creditAppData["city"];
            $addressData["state"] = $creditAppData["state"];
            $addressData["pincode"] = $creditAppData["postal_code"];
            $addressData["addressType"] = "current";
            $addressData["residenceType"] = SmartList::getFieldDescription($mvData["residency_type"]);
            $payload["addressList"] = [$addressData];
            $payload["emailList"] = [array(
                "email" => $creditAppData["email"],
                "type" => "primary_device"
            )];
            $headersArray = array(
                "token: $token",
                "Content-Type: application/json"
            );
            $upwardAppModel = new UpwardsAppModel();
            $response = $upwardAppModel->curlCommonFunction($url, $payload, $headersArray);
            if($response['status'] == "success"){
                ErrorLogModel::LogError($response['status'], 200, "MoneyView: ".$response["message"]."=>".$response["leadId"],$appId);
                return $response["leadId"] ?? 0;
            }else if($response['status'] == "failure"){
                ErrorLogModel::LogError($response['status'], 400, "MoneyView: ".$response["message"]."=>".$response["leadId"],$appId);
                return $response["leadId"] ?? 0;
            } else {
                ErrorLogModel::LogError($response['status'], 400, "MoneyView Erro: ".$response["message"],$appId);
                return "";
            }
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message,$appId);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }
    }

    public function getJourneyUrl($lenderSystemId){
        try{
            $moneyViewBaseUrl = config('constants.moneyviewApiBaseUrl');
            $journey = "v1/journey-url/".$lenderSystemId;
            $url = $moneyViewBaseUrl.$journey;
            $token = $this->getMoneyviewAccessToken();
            $headersArray = array(
                "token: $token",
                "Content-Type: application/json"
            );
            $response = $this->curlCommonFunctionGetMethod($url, $headersArray);
            if(!empty($response)){
                if($response['status'] == "success"){
                    ErrorLogModel::LogError($response['status'], 200, "MoneyView: ".$response["message"]."=>".$lenderSystemId,);
                    return $response["pwa"] ?? "";
                }else if($response['status'] == "failure"){
                    ErrorLogModel::LogError($response['status'], 400, "MoneyView: ".$response["message"]."=>".$lenderSystemId);
                    return "";
                }
                ErrorLogModel::LogError($response['status'], 400, "MoneyView: ".$response["message"]."=>".$lenderSystemId);
                $errolog = new ErrorLogModel();
                //return $errolog->genericMsg();
                return "";
            }else{
                $message = "MoneyView: Unable to get Journey Url for given :".$lenderSystemId;
                ErrorLogModel::LogError($status = 400, 400, $message);
                $errolog = new ErrorLogModel();
                //return $errolog->genericMsg();
                return "";
            }
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }
    }

    public function getMvStatus($lenderSystemId){
        try{
            $moneyViewBaseUrl = config('constants.moneyviewApiBaseUrl');
            $journey = "v1/lead/status/".$lenderSystemId;
            $url = $moneyViewBaseUrl.$journey;
            $token = $this->getMoneyviewAccessToken();
            $headersArray = array(
                "token: $token",
                "Content-Type: application/json"
            );
            $response = $this->curlCommonFunctionGetMethod($url, $headersArray);

            if(!empty($response)){
                if($response["status"] == "success"){
                    ErrorLogModel::LogError($response['status'], 200, "MoneyView: ".$response["leadStatus"].$response["message"],$lenderSystemId);
                    $lapStatus = OfferStatusModel::getLapStatus("MoneyView",$response["leadStatus"]);
                    return $lapStatus ?? "";
                }else if($response["status"] == "failure"){
                    $message = "MoneyView: Failure Unable to get mv status";
                    ErrorLogModel::LogError($response['status'], 400, "MoneyView: ".$response["message"]."=>".$message,$lenderSystemId);
                    return $response["leadStatus"] ?? "";
                }
                $message = "MoneyView: Error Unable to get mv status";
                ErrorLogModel::LogError($response['status'], 400, "MoneyView: ".$response["message"]."=>".$message,$lenderSystemId);
                $lapStatus = OfferStatusModel::getLapStatus("MoneyView",$response["leadStatus"]);
                return $lapStatus ?? $response['status'];
            }else{
                $message = "MoneyView: Unable to get mv status for given :".$lenderSystemId;
                ErrorLogModel::LogError($status = 400, 400, $message,$lenderSystemId);
                $lapStatus = OfferStatusModel::getLapStatus("MoneyView",$response["leadStatus"]);
                return $lapStatus ?? $response['status'];
            }
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message,$lenderSystemId);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }
    }

    /**
     * Method is designed for GET Curl call. There is an issue with post Curl Call. So, created a new GET method.
     * common CURL function to call lenders APIs.
     */
    public function curlCommonFunctionGetMethod($url,$headersArray){
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => $headersArray,
        ));

        $json_response = curl_exec($curl);
        $response = json_decode($json_response, true);
        curl_close($curl);
        return $response;
    }

    public function checkMoneyviewEligibility($panId,$app_id=""){
        try{
            $moneyViewBaseUrl = config('constants.moneyviewApiBaseUrl');
            $filter = "v1/lead/filter/pan/".$panId;
            $url = $moneyViewBaseUrl.$filter;
            $token = $this->getMoneyviewAccessToken();
            $headersArray = array(
                "token: $token",
                "Content-Type: application/json"
            );
            $response = $this->curlCommonFunctionGetMethod($url, $headersArray);

            if(!empty($response)){
                if($response["status"] == "success"){
                    if($response["panValidationStatus"] == "valid"){
                        ErrorLogModel::LogError($response['status'], 200, "MoneyView Eligibility: valid panValidationStatus = ".$panId,$app_id);
                        return true;
                    }else{
                        ErrorLogModel::LogError($response['status'], 400, "MoneyView Eligibility: Invalid panValidationStatus = ".$panId,$app_id);
                        return 0;
                    }
                }
                $message = "MoneyView: Error Unable to get eligibility";
                ErrorLogModel::LogError($response['status'], 400, "MoneyView: ".$message,$app_id);
                return 0;
            }else{
                $message = "MoneyView: Error Unable to get Eligibity given :".$app_id;
                ErrorLogModel::LogError($status = 400, 400, $message,$app_id);
                return 0;
            }
       } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }
    }

	public function buildArrayForMoneyViewToSFDC($moneyViewUpdateData)
	{
        $moneyViewSFDC = array();
		$moneyViewSFDC['Id'] = $moneyViewUpdateData->creditapp_uid;
		$moneyViewSFDC['Gender'] = SmartList::getFieldDescription($moneyViewUpdateData->gender);
		$moneyViewSFDC['LenderName'] = $moneyViewUpdateData->lender_name;
		$moneyViewSFDC['TermsOfUse'] = ($moneyViewUpdateData->term_of_use == 1) ? true : false;
		$moneyViewSFDC['EducationLevel'] = SmartList::getFieldDescription($moneyViewUpdateData->educational_level);
		$moneyViewSFDC['SalaryPaymentMode'] = SmartList::getFieldDescription($moneyViewUpdateData->salary_payment_mode);
		$moneyViewSFDC['PreferNetbanking'] = ($moneyViewUpdateData->prefer_net_banking == 1) ? true : false;
		$moneyViewSFDC['ResidencyType'] = SmartList::getFieldDescription($moneyViewUpdateData->residency_type);
		$moneyViewSFDC['Created'] = $moneyViewUpdateData->created_at;
		$moneyViewSFDC['MisStatus'] = $moneyViewUpdateData->mis_status;
		$moneyViewSFDC['Updated'] = $moneyViewUpdateData->updated_at;
		$moneyViewSFDC['LenderSystemId'] = $moneyViewUpdateData->lender_system_id;
		$moneyViewSFDC['MerchantLocationId'] = $moneyViewUpdateData->merchant_tracking_id;
		$moneyViewSFDC['SelectedOffer']['EMI'] = $moneyViewUpdateData->emi;
		$moneyViewSFDC['SelectedOffer']['Amount'] = $moneyViewUpdateData->amount;
		$moneyViewSFDC['SelectedOffer']['TermsMonth'] = $moneyViewUpdateData->term_months;
		$moneyViewSFDC['SelectedOffer']['Fees'] = $moneyViewUpdateData->processing_fees;
		$moneyViewSFDC['SelectedOffer']['Created'] = $moneyViewUpdateData->created_at;
		$moneyViewSFDC['SelectedOffer']['Rate'] = $moneyViewUpdateData->annual_interest_rate;
		return $moneyViewSFDC;
	}
}
