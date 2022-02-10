<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use App\Models\CreditProspect;
use App\Models\ExternalConnectorsModel;
use DB;
use Exception;

class CreditApp extends Model
{
    use HasFactory;

    protected $table = "credit_app";
    protected $primaryKey = "creditapp_uuid";
    public $timestamps = true;
    public $incrementing = false;

    public function savePersonalInformationiInApp($request)
    {
        //update on return profile
        if (!empty($request->creditapp_uid)) {
            try {
                $creditAppData = CreditApp::where('creditapp_uuid', trim($request->creditapp_uid))->first();
                if (empty($creditAppData)) {
                    return response([
                        'success' => 'false',
                        'message' => 'Invalid App ID'
                    ], 400);
                }
                //$creditAppData->creditapp_uuid = $request->creditapp_uid;
                $creditAppData->creditprospect_uuid = $creditAppData->creditprospect_uuid;
                $creditAppData->first_name = $request['first_name'];
                $creditAppData->middle_name = $request['middle_name'];
                $creditAppData->last_name = $request['last_name'];
                $creditAppData->birth_date = $request['birth_date'];
                $creditAppData->tin = $request['tin'];
                $creditAppData->email = $request['email'];
                $creditAppData->mobile_phone_number = $request['mobile_phone_number'];
                $creditAppData->address1 = $request['address1'];
                $creditAppData->address2 = $request['address2'];
                $creditAppData->address3 = $request['address3'];
                $creditAppData->city = $request['city'];
                $creditAppData->state = $request['state'];
                $creditAppData->postal_code = $request['postal_code'];
                $creditAppData->country = $request['country'];
                $creditAppData->employment_status_code = $request['employment_status_code'];
                $creditAppData->annual_income = $request['annual_income'];
                $creditAppData->credit_amount = $request['credit_amount'];
                $creditAppData->currency_code = empty($request['currency_code']) ? 'INR' : $request['currency_code'];
                $creditAppData->marketing_consent = $request['marketing_consent'];
                $creditAppData->allow_emails = $request['allow_emails'];
                $creditAppData->allow_sms = $request['allow_sms'];
                $creditAppData->submitted = empty($request['submitted']) ? 1 : $request['submitted'];
                $creditAppData->readings = $request['readings'];
                $creditAppData->autosaved = $request['autosaved'];
                $creditAppData->attempts = $request['attempts'];
                $creditAppData->when_last_attempted = $request['when_last_attempted'];
                $creditAppData->timeout_resets = $request['timeout_resets'];
                $creditAppData->servicing_system_id = $request['servicing_system_id'];
                $creditAppData->monthly_income = empty($request['monthly_income']) ? round($request['annual_income'] / 12) : $request['monthly_income'];
                $creditAppData->knockout_lenders = $request['knockout_lenders'];
                $creditAppData->submission = $request['submission'];
                if ($creditAppData->save()) {
                    //$statusOnOff = ExternalConnectorsModel::externalConnects('PHPTOSF');
                    //if ($statusOnOff == 1) {
                       // $this->storeDataintoSFDC($creditAppData->creditapp_uuid);  //code to save data into salesforce
                    //}
                    return response([
                        'success' => 'true',
                        'message' => 'Record Updated Successfully!',
                        'app_id' => $creditAppData->creditapp_uuid
                    ], 200);
                } else {
                    return response([
                        'success' => 'false',
                        'message' => 'something went wrong!'
                    ], 400);
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
        } else {
            try {
                $this->creditapp_uuid = (string) Str::uuid();
                $creditProspectUuid = $this->saveCreditProspectData($request, $this->creditapp_uuid);
                $this->creditprospect_uuid = $creditProspectUuid;
                $this->first_name = $request['first_name'];
                $this->middle_name = $request['middle_name'];
                $this->last_name = $request['last_name'];
                $this->birth_date = $request['birth_date'];
                $this->tin = $request['tin'];
                $this->email = $request['email'];
                $this->mobile_phone_number = $request['mobile_phone_number'];
                $this->address1 = $request['address1'];
                $this->address2 = $request['address2'];
                $this->address3 = $request['address3'];
                $this->city = $request['city'];
                $this->state = $request['state'];
                $this->postal_code = $request['postal_code'];
                $this->country = $request['country'];
                $this->employment_status_code = $request['employment_status_code'];
                $this->annual_income = $request['annual_income'];
                $this->credit_amount = $request['credit_amount'];
                $this->currency_code = empty($request['currency_code']) ? 'INR' : $request['currency_code'];
                $this->marketing_consent = $request['marketing_consent'];
                $this->allow_emails = $request['allow_emails'];
                $this->allow_sms = $request['allow_sms'];
                $this->submitted = empty($request['submitted']) ? 1 : $request['submitted'];
                $this->readings = $request['readings'];
                $this->autosaved = $request['autosaved'];
                $this->attempts = $request['attempts'];
                $this->when_last_attempted = $request['when_last_attempted'];
                $this->timeout_resets = $request['timeout_resets'];
                $this->servicing_system_id = $request['servicing_system_id'];
                $this->monthly_income = empty($request['monthly_income']) ? round($request['annual_income'] / 12) : $request['monthly_income'];
                $this->knockout_lenders = $request['knockout_lenders'];
                $this->submission = $request['submission'];

                if ($this->save()) {
                    //$statusOnOff = ExternalConnectorsModel::externalConnects('PHPTOSF');
                    //if ($statusOnOff == 1) {
                        //$this->storeDataintoSFDC($this->creditapp_uuid);  //code to save data into salesforce
                    //}
                    return response([
                        'success' => 'true',
                        'message' => 'Added Record Successfully!',
                        'app_id' => $this->creditapp_uuid
                    ], 200);
                } else {
                    return response([
                        'success' => 'false',
                        'message' => 'something went wrong!'
                    ], 400);
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
    }

    public function saveCreditProspectData($request, $creditInflightAppId)
    {
        try {
            $creditProspectData = CreditProspect::where('mobile_phone_number', $request->mobile_phone_number)->orWhere('email', $request->email)->first();
            if (empty($creditProspectData)) {
                return response([
                    'success' => 'false',
                    'message' => 'You have entered incorrect phone or email!'
                ], 400);
            }

            $creditProspectData->first_name = $request['first_name'];
            $creditProspectData->middle_name = $request['middle_name'];
            $creditProspectData->last_name = $request['last_name'];
            $creditProspectData->birth_date = $request['birth_date'];
            $creditProspectData->tin = $request['tin'];
            $creditProspectData->credit_amount = $request['credit_amount'];
            $creditProspectData->email = $request['email'];
            $creditProspectData->is_editing = 0; // false
            $creditProspectData->mobile_phone_number = $request['mobile_phone_number'];
            $creditProspectData->inflight_credit_app_id = $creditInflightAppId;
            if ($creditProspectData->save()) {
                return $creditProspectData->credituid;
            } else {
                return response([
                    'success' => 'false',
                    'message' => 'something went wrong!'
                ], 400);
            }
        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message, $creditInflightAppId);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message, $creditInflightAppId);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }
    }

    public function storeDataintoSFDC($app_id)
    {
        try {
            $creditAppData = CreditApp::where('creditapp_uuid', trim($app_id))->first()->toArray();
            $creditData = array();
            $creditData['Id'] = $creditAppData['creditapp_uuid'];
            $creditData['FirstName'] = $creditAppData['first_name'];
            $creditData['MiddleName'] = $creditAppData['middle_name'];
            $creditData['LastName'] = $creditAppData['last_name'];
            $creditData['PartialTin'] = substr($creditAppData['tin'], 0, 4);
            $creditData['ObfuscatedTin'] = (string) Str::uuid();
            $creditData['BirthDate'] = $creditAppData['birth_date'];
            $creditData['Email'] = $creditAppData['email'];
            $creditData['MobilePhoneNumber'] = $creditAppData['mobile_phone_number'];
            $creditData['PostalCode'] = $creditAppData['postal_code'];
            $creditData['City'] = $creditAppData['city'];
            $creditData['Country'] = $creditAppData['country'];
            $creditData['CreditAmount'] = $creditAppData['credit_amount'];
            $creditData['CurrencyCode'] = $creditAppData['currency_code'];
            $creditData['StateProv'] = $creditAppData['state'];
            $creditData['Addr1'] = $creditAppData['address1'];
            $creditData['Addr2'] = $creditAppData['address2'];
            $creditData['EmploymentStatusCode'] = $creditAppData['employment_status_code'];
            $creditData['MonthlyIncome'] = $creditAppData['monthly_income'];
            $creditData['AllowSms'] = $creditAppData['allow_sms'] == 1 ? true : false;
            $creditData['AllowEmail'] = $creditAppData['allow_emails'] == 1 ? true : false;
            $creditData['MarketingConsent'] = $creditAppData['marketing_consent'] == 1 ? true : false;
            $creditData['Submitted'] = $creditAppData['submitted'] == 1 ? true : false;
            $creditData['CreditProspectId'] = $creditAppData['creditprospect_uuid'];
            $creditData['Created'] = $creditAppData['created_at'];
            $creditData['updated'] = $creditAppData['updated_at'];
            $creditData['Lenders'] = $creditAppData['knockout_lenders'];
            //credit prospect data;
            $creditProspectData = CreditProspect::where('credituid', $creditAppData['creditprospect_uuid'])->first()->toArray();
            $creditData['CreditProspect']['CreditProspectId'] = $creditAppData['creditprospect_uuid'];
            $creditData['CreditProspect']['created'] = $creditProspectData['created_at'];
            $creditData['CreditProspect']['ChannelId'] = $creditProspectData['channel_id'];
            $creditData['CreditProspect']['MerchantLocationId'] = $creditProspectData['merchant_location_id'];
            $creditData['CreditProspect']['MerchantName'] = $creditProspectData['merchant_name'];

            $content = json_encode($creditData);

            $response = $this->getSFAccessToken();
            $url = $response['instance_url'] . "/services/apexrest/application";
            $access_token = $response['access_token'];
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(
                $curl,
                CURLOPT_HTTPHEADER,
                array(
                    "Authorization: Bearer $access_token",
                    "Content-type: application/json"
                )
            );
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
            $json_response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);
            $response = json_decode($json_response, true);
            return $response;
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

    public function getSFAccessToken()
    {
        try {
            $token_url = config('salesforce.SfInstance');
            $clientId = config('salesforce.SfClientId');
            $clientSecret = config('salesforce.clientSecret');
            $username = config('salesforce.username');
            $password = config('salesforce.password');

            $params = array(
                'grant_type' => 'password',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'username' => $username,
                'password' => $password
            );

            $curl = curl_init($token_url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            $json_response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($status != 200) {
                die("Error: call to token URL $token_url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
            }
            curl_close($curl);
            $response = json_decode($json_response, true);
            return $response;
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($code, $code, $message);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg();
        }
    }

    public function verifyUsingTin($request)
    {
        try {
            $birthdate = trim($request->birth_date);
            $tin = trim($request->tin);
            $creditProspectId = trim($request->credit_prospect_id);
            $creditProspectData = DB::table('credit_app')
                ->leftJoin('credit_prospect', 'credit_prospect.credituid', '=', 'credit_app.creditprospect_uuid')
                ->where('credit_app.birth_date', $birthdate)
                ->where('credit_app.tin', $tin)
                ->where('credit_app.creditprospect_uuid', $creditProspectId)
                ->first();
            // if(empty($birthdate) && empty($tin)){
            //     return true;
            // }
            if (empty($creditProspectData)) {
                return response([
                    'success' => 'false',
                    'message' => 'Invalid PAN or Birthdate'
                ], 400);
            }
            if ($request->is_existing == 1) {
                if (!empty($creditProspectData)) {
                    return response([
                        'success' => 'true',
                        'message' => 'Data found',
                        'app_id' => $creditProspectData->inflight_credit_app_id
                    ], 200);
                } else {
                    return response([
                        'success' => 'false',
                        'message' => 'Incorrect Prospect Id'
                    ], 400);
                }
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


    public function profileUser($app_id)
    {

        try {
            if (!empty($app_id)) {
                $creditAppData = CreditApp::where('creditapp_uuid', trim($app_id))->first();
                if (!empty($creditAppData)) {
                    return response([
                        'success' => 'true',
                        'data' => $creditAppData
                    ], 200);
                } else {
                    return response([
                        'success' => 'true',
                        'message' => 'Invalid App Id. No data found'
                    ], 400);
                }
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

    public function patchPersonalData($request, $id)
    {
        try {
            $firstName = trim($request->FirstName);
            $lastName = trim($request->LastName);
            $birthDate = trim($request->BirthDate);
            $email = trim($request->Email);
            $postalcode = trim($request->PostalCode);
            $address1 = trim($request->Addr1);
            $address2 = trim($request->Addr2);
            $mobileNumber = trim($request->MobilePhoneNumber);
            $monthlyIncome = trim($request->MonthlyIncome);
            $token = $request->header('token');
            if(empty($token)) {
                return response([
                    'success' => 'false',
                    'message' => 'No token found'
                ], 400);
            }
            $tokenCnt = PersonalAccessToken::checkTokenExpire(trim($token),trim($request->tokenId));

            if($tokenCnt == 1){
                $creditdata = CreditApp::where("creditapp_uuid", $id)->first();
                if (empty($creditdata)) {
                    return response([
                        'success' => 'false',
                        'message' => 'Invalid App ID'
                    ], 400);
                }
                $creditdata->first_name = $firstName;
                $creditdata->last_name = $lastName;
                $creditdata->birth_date = $birthDate;
                $creditdata->postal_code = $postalcode;
                $creditdata->address1 = $address1;
                $creditdata->address2 = $address2;
                $creditdata->monthly_income = $monthlyIncome;
                $creditdata->email = $email;
                $this->updateCreditProspectfromSF($creditdata);
                if ($creditdata->save()) {
                    return response([
                        'success' => 'true',
                        'message' => 'Record Has been Updated'
                    ], 200);
                } else {
                    return response([
                        'success' => 'false',
                        'message' => 'Something went wrong'
                    ], 400);
                }
            }else{
                 return response([
                     'success' => 'false',
                     'message' => 'Invalid token or token_id'
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

    public function updateCreditProspectfromSF($creditData)
    {
        try {
            $creditProspect = CreditProspect::where('credituid', $creditData['creditprospect_uuid'])->first();
            if (empty($creditProspect)) {
                return 0;
            }
            $creditProspect->first_name    = $creditData['first_name'];
            $creditProspect->last_name    = $creditData['last_name'];
            $creditProspect->mobile_phone_number = $creditData['mobile_phone_number'];
            $creditProspect->email    = $creditData['email'];
            $creditProspect->birth_date    = $creditData['birth_date'];
            $creditProspect->save();
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
}
