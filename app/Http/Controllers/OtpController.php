<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\CreditProspect;
use App\Models\Otp;
use Mail;
use JWTAuth;


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

        $mobileNo = trim($request->mobile_phone_number);
        $emailId = trim($request->email);
        $phoneCode = trim($request->mobile_phone_code);
        if($request->issend_toemail == 1 && empty($emailId)){
            return '{ "success" : "fail" , "message" : "Please enter email Only" }';
        }
        $otp = rand(100000, 999999);
        $flag = 0;
        if (is_numeric($mobileNo)) {

            $application_name = config('constants.application_name');
            $senderId = config('constants.sender');

            $message = 'OTP for ' . $application_name . ' Registration is : ' . $otp;
            $flag = $this->sendMessage($senderId, $mobileNo, $message, $phoneCode);
           
            if ($flag == 0) {
                return '{ "status" : "fail" , "message" : "Some Error occured While sending OTP" } ';
            }
        } else {
            $messagePage = "mail"; // mail message in resources/views.
            $subject = "Your CreditLinks One-Time Password";
            $flag = $this->sendEmail($messagePage, $emailId, $subject, $otp);
        }
        if (is_numeric($mobileNo)) {
            $creditProspectdata = CreditProspect::where('mobile_phone_number', $mobileNo)->first();
        } else {
            $creditProspectdata = CreditProspect::where('email', $emailId)->first();
        }

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
        $otpInsert->user_id = $creditProspectdata['user_id']; // primary key of credit prospect table.
        if ($otpInsert->save()) {
            $maskId = $this->maskEmailOrPhone($otpInsert->device_locator);
            return ' { "success" : "true" , "message" : "OTP has been sent to '.$maskId.'" }';
        } else {
            return ' { "success" : "fail" , "message" : "Some Error occured While sending OTP" }';
        }
    }

  
    public function sendMessage($senderId, $mobileNo, $message, $phoneCode)
    {

        $authKey = config('constants.authkey');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.authkey.io/request?authkey=$authKey&mobile=$mobileNo&
            country_code=$phoneCode&sms=$message&sender=$senderId",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            //echo "cURL Error #:" . $err;
            return 0;
        } else {
            return $response;
            //return 1;
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
    }

    /**
     * @param \Illuminate\Http\Request $request
     * 
     * otp login api
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->only('code', 'device_locator');
        $otp = trim($request->code);
        $deviceLocator = trim($request->device_locator);
        $expiry = config('constants.otpexpire'); //defined in constants.php file

        //$expiryTime = date('Y-m-d H:i:s',strtotime("-".$expiry));
        //where('created_at', '>=', $expiryTime)->
        $checkOtp = Otp::where('device_locator', $deviceLocator)->where('used', 0)->where('code', $otp)->count();

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
            ], 200);
        } else {
            return response([
                'success' => 'fail',
                'message' => 'Invalid Otp'
            ], 200);
        }
    }

    public function logout(Request $request)
    {
        //valid credential
        $validator = Validator::make($request->only('token'), [
            'token' => 'required'
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

		//Request is validated, do logout        
        try {
            JWTAuth::invalidate($request->token);
 
            return response()->json([
                'success' => true,
                'message' => 'User has been logged out'
            ]);
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


    public function storeBasicDetails(Request $request){
        $creditProspectUpdate = CreditProspect::where('mobile_phone_number',$request->mobile_phone_number)->where('email',$request->email)->first();
     
        if(!empty($creditProspectUpdate)){
            $creditProspectUpdate->credituid = $creditProspectUpdate->credituid;
            $creditProspectUpdate->channel_id = $request['channel_id'];
            $creditProspectUpdate->email = $request['email'];
            $creditProspectUpdate->mobile_phone_code = $request['mobile_phone_code'];
            $creditProspectUpdate->mobile_phone_number = $request['mobile_phone_number'];
            $creditProspectUpdate->role_id = 1;
            if($creditProspectUpdate->save()){
               // return $creditProspectUpdate->credituid;
                return response([
                    'success' => 'true',
                    'message' => 'Added Record Successfully!',
                    'app_id' => $creditProspectUpdate->credituid

                ],200);
            }else{
                return response([
                    'success' => 'false',
                    'message' => 'something went wrong!'
                ],200);
            }
        }else{
            $obj  = new CreditProspect();
            $obj->credituid = (string) Str::uuid();
            $obj->channel_id = $request['channel_id'];
            $obj->email = $request['email'];
            $obj->mobile_phone_code = $request['mobile_phone_code'];
            $obj->mobile_phone_number = $request['mobile_phone_number'];
            $obj->role_id = 1;
            if($obj->save()){
                //return $obj->credituid;
                return response([
                    'success' => 'true',
                    'message' => 'Added Record Successfully!',
                    'app_id' => $obj->credituid
                ],200);
            }else{
                return response([
                    'success' => 'false',
                    'message' => 'something went wrong!'
                ],200);
            }
        }
        
    }   
}
