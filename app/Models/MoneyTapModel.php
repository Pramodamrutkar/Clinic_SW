<?php

namespace App\Models;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UpwardsAppModel;
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
            if($creditAppIdCount != 1){
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

            $lenderCustomerId = $this->createLeadwithMoneyTap($moneyTapUpdateData,$appId);

            $moneyTapUpdateData->lender_customer_id = $lenderCustomerId;

            if($moneyTapUpdateData->save()){
                return Response([
                    'status' => 'true',
                    'message' => 'saved data successfully!',
                    'mvIframeUrl' => $moneyTapUpdateData->journey_url
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

            $payload = array();
            $headersArray = array(
                "Content-Type: application/json",
                "Authorization : Basic $clientIdSecretIdEncoded"
            );

            $upwardAppModel = new UpwardsAppModel();
            $response = $upwardAppModel->curlCommonFunction($url, $payload, $headersArray);
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

            $payload = array(
                    "name" => $creditAppData["firstname"]." ".$creditAppData["lastname"],
                    "phone" => $creditAppData["mobile_phone_number"],
                    "emailId" => $creditAppData["email"],
                    "panNumber" => $creditAppData["tin"],
                    "dateOfBirth" => $creditAppData["birth_date"],
                    "incomeInfo" => array(
                        "declared" => $creditAppData["monthly_income"],
                        "mode" => "NETBANKING",
                        "bankIfscPrefixes" => array("HDFC")
                    ),
                    "companyType"=> "PRIVATE_LIMITED",
                    "jobType" => $creditAppData["employment_status_code"],
                    "gender" => "MALE",
                    "residenceType"=> "OWNED_BY_SELF_SPOUSE",
                    "totalWorkExperienceInMonths"=> 24,
                    "currentWorkExperienceInMonths"=> 24,
                    "currentCityDurationInMonths"=> 24,
                    "currentHomeAddressDurationInMonths"=> 24,
                    "maritalStatus"=> "MARRIED",
                    "officeEmail"=> "john@office.com",
                    "companyName"=> "Microsoft",
                    "employmentType"=> "FULL_TIME",
                    "homeAddress"=> array(
                        "addressLine1"=> "987, some other street",
                        "pincode"=> "560002"
                    ),
                    "officeAddress" => array(
                        "addressLine1"=> "123, Some street",
                        "pincode"=> "560001"
                    )
            );

            $headersArray = array(
                "Content-Type: application/json",
                "Authorization : Bearer $token"
            );

            $upwardAppModel = new UpwardsAppModel();
            $response = $upwardAppModel->curlCommonFunction($url, $payload, $headersArray);
            if(!empty($response)){
                    return $response["customerId"];
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
                "Content-Type: application/json",
                "Authorization : Bearer $token"
            );

            $upwardAppModel = new UpwardsAppModel();
            $response = $upwardAppModel->curlCommonFunction($url, $payload, $headersArray);
            if(!empty($response)){
                    return $response;
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


}
