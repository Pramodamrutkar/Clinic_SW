<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Database\QueryException;
use App\Models\CreditProspect;
use App\Models\ErrorLogModel;
use App\Models\Otp;
use Mail;
use JWTAuth;
use DB;
use Exception;
use ParseError;

class OtpController extends Controller
{
    /**
     * send a otp to request mobile no or email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendOtp(Request $request)
    {
        try {
            $mobileNo = trim($request->mobile_phone_number);
            $emailId = trim($request->email);
            $phoneCode = trim($request->mobile_phone_code);
            if ($request->issend_toemail == 1 && empty($emailId)) {
                return '{ "success" : "fail" , "message" : "Please enter email Only" }';
            }
            $otp = rand(100000, 999999);
            $flag = 0;
            if (is_numeric($mobileNo)) {

                $application_name = config('constants.application_name');
                $senderId = config('constants.sender');
                $message = 'Here is your one-time password to complete your account profile with CreditLinks [CRLINK] ' . $otp;
                $flag = $this->sendMessage($senderId, $mobileNo, $message, $phoneCode);
                if ($flag == 0) {
                    return response([
                        'success' => 'fail',
                        'message' => "Some Error occured While sending OTP"
                    ], 400);
                }
            } else {
                $messagePage = "mail"; // mail message in resources/views.
                $subject = "Your CreditLinks One-Time Password";
                $flag = $this->sendEmail($messagePage, $emailId, $subject, $otp);
            }
           
            if(!empty($mobileNo)){
                $creditProspectdata = CreditProspect::where('mobile_phone_number', $mobileNo)->first();
            }else if(!empty($emailId)){
                $creditProspectdata = CreditProspect::where('email', $emailId)->first();
            }
            
            $expireTime = config('constants.otpexpire');
            
            $otpInsert = new Otp();
            $otpInsert->code = $otp;
            $otpInsert->otpuid = (string) Str::uuid();
            if ($request->issend_toemail == 1) {
                $otpInsert->communication_mode = "EMAIL";
                $otpInsert->device_locator = $emailId;
            } else {
                $otpInsert->communication_mode = "PHONE";
                $otpInsert->device_locator = $mobileNo;
            }
            $otpInsert->user_id = empty($creditProspectdata['user_id']) ? 0 : $creditProspectdata['user_id']; // primary key of credit prospect table.
            $otpInsert->expire_otp_time = date("Y-m-d H:i:s", strtotime(date('Y-m-d H:i:s') . "$expireTime seconds"));
            $otpInsert->context = $creditProspectdata["credituid"] ?? "";
            if ($otpInsert->save()) {
                $maskId = $this->maskEmailOrPhone($otpInsert->device_locator);
                return response([
                    'success' => 'true',
                    'message' => "OTP has been sent to '.$maskId.'"
                ], 200);
            } else {
                return response([
                    'success' => 'fail',
                    'message' => "Some Error occured While sending OTP"
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


    public function sendMessage($senderId, $mobileNo, $message, $phoneCode)
    {

        try {
            $authKey = config('constants.authkey');
            $peId = config('constants.pe_idEntityId');
            $templateId = config('constants.templateId');

            $str = "authkey=" . $authKey . "&mobile=" . $mobileNo . "&country_code=" . $phoneCode . "&sms=" . $message . "&sender=" . $senderId . "&pe_id=" . $peId . "&template_id=" . $templateId;
            $url1 = "https://api.authkey.io/request?" . $str;
            $url = str_replace(' ', '%20', $url1);
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
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if ($err) {
                return 0;
            } else {
                return 1;
            }
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            echo ErrorLogModel::genericMessage();
        }
    }

    public function sendEmail($messagePage, $toEmail, $subject, $otp, $attachment = '')
    {

        $data = array("otp" => $otp, "toEmail" => $toEmail, "subject" => $subject, 'attachment' => $attachment);
        Mail::send($messagePage, $data, function ($message) use ($data) {
            $message->to($data['toEmail'])->subject($data['subject']);
            $message->from('noreply@creditlinks.in', 'CreditLinks');
            if (!empty($data['attachment'])) {
                $message->attach($data['attachment']);
            }
        });
        return 1;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * 
     * otp login api
     */
    public function authenticate(Request $request)
    {
        try {
            $credentials = $request->only('code', 'device_locator');
            $otp = trim($request->code);
            $deviceLocator = trim($request->device_locator);
            $creditProspectId = trim($request->credit_prospect_id);
            if (empty($creditProspectId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Prospect ID',
                ], 400);
            }
            $expiryTime = date('Y-m-d H:i:s');

            $checkOtp = Otp::where('device_locator', $deviceLocator)->where('used', 0)->where('code', $otp)->where('expire_otp_time', '>=', $expiryTime)->get()->count();

            $creditProspectdata = CreditProspect::where('credituid', $creditProspectId)->first();
            if (empty($creditProspectdata)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Prospect Data',
                ], 400);
            }
            if ($checkOtp > 0) {
                //token generation
                try {
                    if (!$token = JWTAuth::attempt($credentials)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Login credentials are invalid.',
                        ], 400);
                    }
                } catch (JWTException $e) {
                    return $credentials;
                    return response()->json([
                        'success' => false,
                        'message' => 'Could not create token.',
                    ], 500);
                }
                if (!empty($token)) {
                    Otp::where('code', $otp)->update(['used' => 1]);
                }

                return response([
                    'success' => 'true',
                    'message' => 'Verified Otp',
                    'token' => $token,
                    'data' => $creditProspectdata
                ], 200);
            } else {
                return response([
                    'success' => 'fail',
                    'message' => 'Invalid Otp'
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

    public function logout(Request $request)
    {
        //valid credential
        // $validator = Validator::make($request->only('token'), [
        //     'token' => 'required'
        // ]);

        // //Send failed response if request is not valid
        // if ($validator->fails()) {
        //     return response()->json(['error' => $validator->messages()], 200);
        // }

        //Request is validated, do logout        
        try {
            JWTAuth::invalidate($request->token);

            return response()->json([
                'success' => true,
                'message' => 'User has been logged out'
            ], 200);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, user cannot be logged out'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function mask($str, $first, $last)
    {
        $len = strlen($str);
        $toShow = $first + $last;
        return substr($str, 0, $len <= $toShow ? 0 : $first) . str_repeat("*", $len - ($len <= $toShow ? 0 : $toShow)) . substr($str, $len - $last, $len <= $toShow ? 0 : $last);
    }

    function maskEmailOrPhone($string)
    {
        $mail_parts = explode("@", $string);
        $mail_parts[0] = $this->mask($mail_parts[0], 2, 1);
        if (!is_numeric($string)) {
            $domain_parts = explode('.', $mail_parts[1]);
            $domain_parts[0] = $this->mask($domain_parts[0], 2, 1);
        }
        if (!is_numeric($string)) {
            $mail_parts[1] = implode('.', $domain_parts);
        }
        return implode("@", $mail_parts);
    }


    public function storeBasicDetails(Request $request)
    {

        $creditProspectUpdate = CreditProspect::where('mobile_phone_number', $request->mobile_phone_number)->orWhere('email', $request->email)->first();

        $merchantData = DB::table('merchant')
            ->leftJoin('merchant_location', 'merchant.merchant_uid', '=', 'merchant_location.merchant_location_uid')
            ->where('merchant.url_segment', trim($request->url_segment))
            ->first();

        if (!empty($creditProspectUpdate)) {
            try {
                $creditProspectUpdate->credituid = $creditProspectUpdate->credituid;
                $creditProspectUpdate->email = $request['email'];
                $creditProspectUpdate->mobile_phone_code = $request['mobile_phone_code'];
                $creditProspectUpdate->mobile_phone_number = $request['mobile_phone_number'];
                $creditProspectUpdate->role_id = 1;
                $creditProspectUpdate->is_consent_accept = $request['is_consent_accept'];
                $creditProspectUpdate->is_remind_me_later = $request['is_remind_me_later'];
                $creditProspectUpdate->merchant_tracking_id = empty($request->url_segment) ? "" : $request->url_segment;
                $creditProspectUpdate->merchant_name = empty($merchantData->name) ? "" : $merchantData->name;
                $creditProspectUpdate->merchant_location_id = empty($merchantData->merchant_location_uid) ? "" : $merchantData->merchant_location_uid;
                $creditProspectUpdate->updated_at = date("Y-m-d H:i:s");
                if ($creditProspectUpdate->save()) {
                    // return $creditProspectUpdate->credituid;
                    return response([
                        'success' => 'true',
                        'message' => 'Added Record Successfully!',
                        'app_id' => $creditProspectUpdate->credituid

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
                $obj  = new CreditProspect();
                $obj->credituid = (string) Str::uuid();
                $obj->channel_id = $merchantData->channel ?? "";
                $obj->email = $request['email'];
                $obj->mobile_phone_code = $request['mobile_phone_code'];
                $obj->mobile_phone_number = $request['mobile_phone_number'];
                $obj->merchant_tracking_id = empty($request->url_segment) ? "" : $request->url_segment;
                $obj->merchant_name = empty($merchantData->name) ? "" : $merchantData->name;
                $obj->merchant_location_id = empty($merchantData->merchant_location_uid) ? "" : $merchantData->merchant_location_uid;
                $obj->is_consent_accept = $request['is_consent_accept'];
                $obj->is_remind_me_later = $request['is_remind_me_later'];
                $obj->role_id = 1;
          
                if ($obj->save()) {
                    //return $obj->credituid;
                    return response([
                        'success' => 'true',
                        'message' => 'Added Record Successfully!',
                        'app_id' => $obj->credituid
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
}
