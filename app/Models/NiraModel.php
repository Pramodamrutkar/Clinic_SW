<?php

namespace App\Models;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UpwardsAppModel;
use App\Models\SmartList;
use Exception;

class NiraModel extends Model
{
    use HasFactory;

    protected $table = "nira";
    protected $primaryKey = "nira_id";
    public $timestamps = false;

    /**
     * used to save MoneyTap Data
     */
    public function storeNiraData($request,$appId){
       // try{
            $creditAppIdCount = CreditApp::where('creditapp_uuid',$appId)->count();

            if($creditAppIdCount == 0){
                return Response([
                    'status' => 'fail',
                    'message' => 'Invalid AppID'
                ],400);
            }

            $niraUpdateData = NiraModel::where('creditapp_uid',$appId)->first();

            if(empty($niraUpdateData)){
                return Response([
                    'status' => 'fail',
                    'message' => 'No Applied loan exist'
                ],400);
            }

            $niraUpdateData->gender = $request['gender'];
            $niraUpdateData->marital_status = $request['maritalStatus'];
            $niraUpdateData->company_name = $request['organizationName'];
            $niraUpdateData->total_work_experience = $request['experience'];

			$niraUpdateData->existing_total_emi_amount = $request['existingTotalEmiAmount'];
			$niraUpdateData->designation = $request['designation'];
			$niraUpdateData->home_town = $request['hometown'];
			$niraUpdateData->salary_mode = $request['incomeMode'];
			$niraUpdateData->job_sector = $request['jobSector'];
			$niraUpdateData->work_status = $request['workStatus'];

            $lenderCustomerId = 0; // $this->createLeadwithNira($niraUpdateData,$appId);

            $niraUpdateData->lender_customer_id = $lenderCustomerId ?? 0;
            $niraIframeUrl = config('constants.niraIframeUrl');
            if($niraUpdateData->save()){
                //$moneyTapSFDC = $this->buildArrayForMoneyTapToSFDC($niraUpdateData);
                //$casheAppModelObj = new CasheAppModel();
				//$casheAppModelObj->storeAdditionalDataInSFDC($moneyTapSFDC);
                return Response([
                    'status' => 'true',
                    'message' => 'saved data successfully!',
                    'niraIframeUrl' => $niraIframeUrl
                ],200);
            }else{
                return Response([
                    'status' => 'false',
                    'message' => 'Something went wrong'
                ],400);
            }
        /*}  catch (QueryException $e) {
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
        } */
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
    public function createLeadwithNira($niraData,$appId){
        try{
            $creditAppData = CreditApp::where("creditapp_uuid",$appId)->first();

            $moneyTapBaseApiUrl = config('constants.niraApiBaseUrl');
            $url = $moneyTapBaseApiUrl."/createapplication";
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
                    "uuid" =>  $appId,
                    "partnerIdentifier" => 'LOAN2596',
                    "dateofBirth" => $creditAppData["first_name"]." ".$creditAppData["last_name"],
					"gender" => SmartList::getFieldDescription($niraData->gender),
					"maritalStatus"=> SmartList::getFieldDescription($niraData->marital_status),
					"organizationName"=>  $niraData->company_name,
					"employmentType"=>  "FULL_TIME",
					"experienceInMonths"=> intval($niraData->total_work_experience),
					"existingTotalEMIAmount"=> intval($niraData->total_work_experience),
                    "firstName" => $creditAppData["first_name"],
                    "lastName" => $creditAppData["last_name"],
					"personalEmailId" => $creditAppData["email"],
                    "mobileNo" => $creditAppData["mobile_phone_number"],
                    "designation" => $creditAppData["designation"],
                    "pincode"=> $creditAppData["postal_code"],
					"addressLine1"=> $niraData->office_address,
					"addressLine2"=> $niraData->office_address,
					"hometown"=> $niraData->homeTown,
					"salaryMode"=> $niraData->salaryMode,
					"jobSector"=> $niraData->jobSector,
					"workStatus"=> $niraData->workStatus
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
                        ErrorLogModel::LogError(200, 200, "Nira=".json_encode($response));
                        return 0;
                    }
            }else{
                $message = "Unable to create lead for Nira service";
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
                    return $lapStatus ?? "";
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
		$moneyTapSFDC['BankAccountHolderName'] = $moneyTapUpdateData->bank_account_holder_name;
        $moneyTapSFDC['BankIfscCode'] = $moneyTapUpdateData->bank_ifsc_code;
        $moneyTapSFDC['MaritalStatus'] = SmartList::getFieldDescription($moneyTapUpdateData->marital_status);
        $moneyTapSFDC['OfficeEmail'] = $moneyTapUpdateData->office_email;
        $moneyTapSFDC['CompanyName'] = $moneyTapUpdateData->company_name;
        $moneyTapSFDC['CompanyType'] = $moneyTapUpdateData->company_type;
        $moneyTapSFDC['OfficeAddress'] = $moneyTapUpdateData->office_address;
        $moneyTapSFDC['ResidenceType'] = SmartList::getFieldDescription($moneyTapUpdateData->residence_type);
        $moneyTapSFDC['CurrentCityDuration'] = $moneyTapUpdateData->current_city_duration;
        $moneyTapSFDC['CurrentHomeAddressDuration'] = $moneyTapUpdateData->current_home_address_duration;
        $moneyTapSFDC['TotalWorkExperience'] = $moneyTapUpdateData->total_work_experience;
        $moneyTapSFDC['CurrentWorkExperienceInOrg'] = $moneyTapUpdateData->current_work_experience_in_org;
        $moneyTapSFDC['Created'] = $moneyTapUpdateData->created_at;
		$moneyTapSFDC['MisStatus'] = $moneyTapUpdateData->mis_status;
		$moneyTapSFDC['LenderCustomerId'] = $moneyTapUpdateData->lender_customer_id;
        $moneyTapSFDC['SelectedOffer']['EMI'] = $moneyTapUpdateData->emi;
		$moneyTapSFDC['SelectedOffer']['Amount'] = $moneyTapUpdateData->amount;
		$moneyTapSFDC['SelectedOffer']['TermsMonth'] = $moneyTapUpdateData->term_months;
		$moneyTapSFDC['SelectedOffer']['Fees'] = $moneyTapUpdateData->processing_fees;
		$moneyTapSFDC['SelectedOffer']['Created'] = $moneyTapUpdateData->created_at;
		$moneyTapSFDC['SelectedOffer']['Rate'] = $moneyTapUpdateData->annual_interest_rate;
		return $moneyTapSFDC;
	}

}
