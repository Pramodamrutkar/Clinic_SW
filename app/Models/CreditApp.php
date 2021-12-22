<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\CreditProspect;

class CreditApp extends Model
{
    use HasFactory;

    protected $table = "credit_app";

    protected $primaryKey = "app_id";

    public $timestamps = true;


    public function savePersonalInformationiInApp($request)
    {

        $creditProspectUuid = $this->saveCreditProspectData($request);
        $this->creditprospect_uuid = $creditProspectUuid;
        $this->creditapp_uuid = (string) Str::uuid();
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
        $this->submitted = $request['submitted'];
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
            //$this->storeDataintoSFDC($this->creditapp_uuid);  //code to save data into salesforce
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
    }

    public function saveCreditProspectData($request)
    {

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
        if ($creditProspectData->save()) {
            return $creditProspectData->credituid;
            // return response([
            //     'success' => 'true',
            //     'message' => 'Added Record Successfully!',
            //     'app_id' => $creditProspectData->credituid
            // ],200);
        } else {
            return response([
                'success' => 'false',
                'message' => 'something went wrong!'
            ], 400);
        }
    }

    public function storeDataintoSFDC($app_id)
    {
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
        print_r($response);
    }

    public function getSFAccessToken()
    {
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
        // $access_token = $response['access_token'];
        // echo $access_token;
        // echo "<br>";
        // $instance_url= $response['instance_url'];
        // echo $instance_url;
    }
}
