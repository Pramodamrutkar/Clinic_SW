<?php

namespace App\Models;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UpwardsAppModel;
use App\Models\SmartList;
use Exception;

class MoneyTapModel extends Model
{
    use HasFactory;

    protected $table = "money_tap";
    protected $primaryKey = "moneytap_id";
    public $timestamps = true;

    /**
     * used to save MoneyTap Data
     */
    public function storeMoneyTapData($request,$appId){
        try{
            $creditAppIdCount = CreditApp::where('creditapp_uuid',$appId)->count();

            if($creditAppIdCount == 0){
                return Response([
                    'status' => 'fail',
                    'message' => 'Invalid AppID'
                ],400);
            }

            $moneyTapUpdateData = MoneyTapModel::where('creditapp_uid',$appId)->first();

            if(empty($moneyTapUpdateData)){
                return Response([
                    'status' => 'fail',
                    'message' => 'No Applied loan exist'
                ],400);
            }
            $moneyTapUpdateData->gender = $request['gender'];
            $moneyTapUpdateData->income_mode = $request['income_mode'];
            $moneyTapUpdateData->bank_account_holder_name = $request['bank_account_holder_name'];
            $moneyTapUpdateData->bank_ifsc_code = $request['bank_ifsc_code'];
            $moneyTapUpdateData->marital_status = $request['marital_status'];
            $moneyTapUpdateData->office_email = $request['office_email'];
            $moneyTapUpdateData->company_name = $request['company_name'];
            $moneyTapUpdateData->company_type = $request['company_type'];
            $moneyTapUpdateData->office_address = $request['office_address'];
            $moneyTapUpdateData->residence_type = $request['residence_type'];
            $moneyTapUpdateData->current_city_duration = $request['current_city_duration'];
            $moneyTapUpdateData->current_home_address_duration = $request['current_home_address_duration'];
            $moneyTapUpdateData->total_work_experience = $request['total_work_experience'];
            $moneyTapUpdateData->current_work_experience_in_org = $request['current_work_experience_in_org'];
            $statusOnOff = ExternalConnectorsModel::externalConnects("CHECKMTLEAD");

            if($statusOnOff == 1){
                $lenderCustomerId = $this->createLeadwithMoneyTap($moneyTapUpdateData,$appId);
                $moneyTapUpdateData->lender_customer_id = $lenderCustomerId ?? 0;
            }else{
                $moneyTapUpdateData->lender_customer_id = 0;
            }
            $mtIframeUrl = config('constants.mtIframeUrl');
            if($moneyTapUpdateData->save()){
                $mvstatusOnOff = ExternalConnectorsModel::externalConnects("MONEYTAPLAPTOSF");
                if($mvstatusOnOff == 1){
                    $moneyTapSFDC = $this->buildArrayForMoneyTapToSFDC($moneyTapUpdateData);
                    $casheAppModelObj = new CasheAppModel();
                    $casheAppModelObj->storeAdditionalDataInSFDC($moneyTapSFDC);
                }
                return Response([
                    'status' => 'true',
                    'message' => 'saved data successfully!',
                    'moneyTapURL' => $mtIframeUrl
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
            ErrorLogModel::LogError($status = 500, $code, $message,$appId);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message,$appId);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }
    }

    /**
     * Function is used to generate token for moneyTap
    */
    public function getMoneyTapToken(){
        try{
            $moneyTapBaseApiUrl = config('constants.moneyTapApiBaseUrl');
            $moneyTapClientId = config('constants.moneyTapClientId');
            $moneyTapSecretKey = config('constants.moneyTapSecretId');

            $secretKey = $moneyTapClientId.":".$moneyTapSecretKey;
            $clientIdSecretIdEncoded = base64_encode($secretKey);
            $tokenUrl = "/oauth/token?grant_type=client_credentials";
            $url = $moneyTapBaseApiUrl.$tokenUrl;

            $curl = curl_init();
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
                    'Content-Type: application/json',
                    'Authorization: Basic '.$clientIdSecretIdEncoded
                ),
            ));

            $response = curl_exec($curl);
            $response = json_decode($response, true);
            curl_close($curl);
            // $upwardAppModel = new UpwardsAppModel();
            // $response = $upwardAppModel->curlCommonFunction($url, $payload, $headersArray);
            if(!empty($response)){
                    return $response["access_token"];
            }else{
                $message = "Unable to get access token for MoneyTap service";
                ErrorLogModel::LogError(400, 400, $message);
            }
        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
        }
    }
    /**
     * Function used to create a lead with MoneyTap
     */
    public function createLeadwithMoneyTap($moneyTapData,$appId){
        try{
            $creditAppData = CreditApp::where("creditapp_uuid",$appId)->first();

            $token = $this->getMoneyTapToken();
            $moneyTapBaseApiUrl = config('constants.moneyTapApiBaseUrl');
            $url = $moneyTapBaseApiUrl."/v3/partner/buildprofile";
            $jobTypeFromSmartList = SmartList::getFieldDescription($creditAppData["employment_status_code"]);
            if($jobTypeFromSmartList == "Wrkr"){
                $jobType = "SALARIED";
            }else if($jobTypeFromSmartList == "Slf"){
                $jobType = "SELF_EMPLOYED";
            }else if($jobTypeFromSmartList == "Stdnt"){
                $jobType = "STUDENT";
            }else if($jobTypeFromSmartList == "Othr"){
                $jobType = "HOMEMAKER";
            }else if($jobTypeFromSmartList == "Rtird"){
                $jobType = "RETIRED";
            }
            $payload = array(
                    "name" => $creditAppData["first_name"]." ".$creditAppData["last_name"],
                    "phone" => $creditAppData["mobile_phone_number"],
                    "emailId" => $creditAppData["email"],
                    "panNumber" => $creditAppData["tin"],
                    "dateOfBirth" => $creditAppData["birth_date"],
                    "incomeInfo" => array(
                        "declared" => $creditAppData["monthly_income"],
                        "mode" => SmartList::getFieldDescription($moneyTapData->income_mode),
                        "bankIfscPrefixes" => array(substr($moneyTapData->bank_ifsc_code, 0, 4))
                    ),
                    "companyType"=> SmartList::getFieldDescription($moneyTapData->company_type),
                    "jobType" => $jobType,
                    "gender" => SmartList::getFieldDescription($moneyTapData->gender),
                    "residenceType"=> SmartList::getFieldDescription($moneyTapData->residence_type),
                    "totalWorkExperienceInMonths"=> intval($moneyTapData->total_work_experience),
                    "currentWorkExperienceInMonths"=> intval($moneyTapData->current_work_experience_in_org),
                    "currentCityDurationInMonths"=> intval($moneyTapData->current_city_duration),
                    "currentHomeAddressDurationInMonths"=> intval($moneyTapData->current_home_address_duration),
                    "maritalStatus"=> SmartList::getFieldDescription($moneyTapData->marital_status),
                    "officeEmail"=>  $moneyTapData->office_email,
                    "companyName"=>  $moneyTapData->company_name,
                    "employmentType"=>  "FULL_TIME",
                    "homeAddress"=> array(
                        "addressLine1"=> $creditAppData["address1"]." ".$creditAppData["address2"],
                        "pincode"=> $creditAppData["postal_code"]
                    ),
                    "officeAddress" => array(
                        "addressLine1"=> $moneyTapData->office_address,
                        "pincode"=> $creditAppData["postal_code"]
                    )
            );

            $headersArray = array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer '.$token
            );

            $upwardAppModel = new UpwardsAppModel();
            $response = $upwardAppModel->curlCommonFunction($url, $payload, $headersArray);
            if(!empty($response)){
                    if(isset($response["customerId"])){
                        return $response["customerId"];
                    }else{
                        ErrorLogModel::LogError(200, 200, "MoneyTap=".json_encode($response));
                        return 0;
                    }
            }else{
                $message = "Unable to create lead for MoneyTap service";
                ErrorLogModel::LogError(400, 400, $message);
            }
       } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
        }
    }

    /**
     * Function is used to check MoneyTap lead status
     */
    public function getMoneyTapLeadStatus($customerId){
        try{

            $token = $this->getMoneyTapToken();
            $moneyTapBaseApiUrl = config('constants.moneyTapApiBaseUrl');
            $url = $moneyTapBaseApiUrl."/v3/partner/customer/details";

            $payload = array(
                "customerId" => $customerId
            );

            $headersArray = array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$token
            );

            $upwardAppModel = new UpwardsAppModel();
            $response = $upwardAppModel->curlCommonFunction($url, $payload, $headersArray);
            if(!empty($response)){
                    ErrorLogModel::LogError(200, 200, "MoneyTap Lead Status => ".json_encode($response));
                    $lapStatus = OfferStatusModel::getLapStatus("MoneyTap",$response["finalApprovalStatus"]);
                    return $lapStatus;
            }else{
                $message = "Unable to get status of customer lead for MoneyTap service";
                ErrorLogModel::LogError(400, 400, $message);
            }
       } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
        }
    }


    public function buildArrayForMoneyTapToSFDC($moneyTapUpdateData)
	{
        $moneyTapSFDC = array();
		$moneyTapSFDC['Id'] = $moneyTapUpdateData->creditapp_uid;
		$moneyTapSFDC['Gender'] = SmartList::getFieldDescription($moneyTapUpdateData->gender);
		$moneyTapSFDC['LenderName'] = $moneyTapUpdateData->lender_name;
		$moneyTapSFDC['IncodeMode'] = SmartList::getFieldDescription($moneyTapUpdateData->income_mode);
		$moneyTapSFDC['BankAccountHolderFullName'] = $moneyTapUpdateData->bank_account_holder_name;
        $moneyTapSFDC['IFSC'] = $moneyTapUpdateData->bank_ifsc_code;
        $moneyTapSFDC['MaritalStatus'] = SmartList::getFieldDescription($moneyTapUpdateData->marital_status);
        $moneyTapSFDC['OfficeEmail'] = $moneyTapUpdateData->office_email;
        $moneyTapSFDC['Company'] = $moneyTapUpdateData->company_name;
        $moneyTapSFDC['CompanyType'] = $moneyTapUpdateData->company_type;
        $moneyTapSFDC['OfficeAddress'] = $moneyTapUpdateData->office_address;
        $moneyTapSFDC['ResidencyType'] = SmartList::getFieldDescription($moneyTapUpdateData->residence_type);
        $moneyTapSFDC['CurrentCityDuration'] = $moneyTapUpdateData->current_city_duration;
        $moneyTapSFDC['CurrentHomeAddressDuration'] = $moneyTapUpdateData->current_home_address_duration;
        $moneyTapSFDC['TotalWorkExperience'] = $moneyTapUpdateData->total_work_experience;
        $moneyTapSFDC['CurrentWorkExperienceInOrg'] = $moneyTapUpdateData->current_work_experience_in_org;
        $moneyTapSFDC['Created'] = $moneyTapUpdateData->created_at;
		$moneyTapSFDC['MisStatus'] = $moneyTapUpdateData->mis_status;
		$moneyTapSFDC['LenderSystemId'] = $moneyTapUpdateData->lender_customer_id;
        $moneyTapSFDC['SelectedOffer']['EMI'] = $moneyTapUpdateData->emi;
		$moneyTapSFDC['SelectedOffer']['Amount'] = $moneyTapUpdateData->amount;
		$moneyTapSFDC['SelectedOffer']['TermsMonth'] = $moneyTapUpdateData->term_months;
		$moneyTapSFDC['SelectedOffer']['Fees'] = $moneyTapUpdateData->processing_fees;
		$moneyTapSFDC['SelectedOffer']['Created'] = $moneyTapUpdateData->created_at;
		$moneyTapSFDC['SelectedOffer']['Rate'] = $moneyTapUpdateData->annual_interest_rate;
		return $moneyTapSFDC;
	}

}
