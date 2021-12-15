<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests;
use App\Models\CreditProspect;
use App\Models\Otp;
use Illuminate\Support\Str;
use Mail;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;
use Illuminate\Support\Facades\Validator;

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

            $otp = rand(100000, 999999);
            $flag = 0;
            if(is_numeric($mobileNo)){

                $application_name = config('constants.application_name');
                $senderId = config('constants.sender');
        
                $message = 'OTP for '.$application_name.' Registration is : '.$otp;
                $flag = $this->sendMessage($senderId, $mobileNo, $message,$phoneCode);
                
                if($flag == 0){
                    return '{ "status" : "fail" , "message" : "Some Error occured While sending OTP" } ';	
                }
               
            }else{
                $messagePage = "mail"; // mail message in resources/views.
                $subject = "Your CreditLinks One-Time Password";
                $flag = $this->sendEmail($messagePage,$emailId,$subject,$otp);
            }   
                if(is_numeric($mobileNo)){
                    $creditProspectdata = CreditProspect::where('mobile_phone_number', $mobileNo)->first(); 
                }else{
                    $creditProspectdata = CreditProspect::where('email', $emailId)->first(); 
                }
                
                $otpInsert = new Otp();
                $otpInsert->code = $otp;
                $otpInsert->otpuid = (string) Str::uuid();
                if($request->issend_toemail == 1){
                    $otpInsert->communication_mode = "EMAIL";
                    $otpInsert->device_locator = $emailId;	     
                }else{
                    $otpInsert->communication_mode = "PHONE";
                    $otpInsert->device_locator = $mobileNo;	   
                } 
                $otpInsert->user_id = $creditProspectdata['user_id']; // primary key of credit prospect table.
                $otpInsert->save();
                if($flag == 1){
                    return ' { "status" : "success" , "message" : "OTP has been sent" } ';	
                }
    }
    
    /**
     * used to send message
     */
    public function sendMessage($senderId,$mobileNo,$message,$phoneCode){

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
                //echo $response;
                return 1;
            }
    }

    /**
     * Used to send an email
     */
    public function sendEmail($messagePage,$toEmail,$subject, $otp, $attachment=''){
        
        $data = array("otp"=>$otp, "toEmail" => $toEmail,"subject" => $subject,'attachment' => $attachment);

        Mail::send($messagePage, $data, function($message) use ($data){
            $message->to($data['toEmail'])->subject($data['subject']);
            $message->from('noreply@creditlinks.in','CreditLinks');
            if(!empty($data['attachment'])){
                $message->attach($data['attachment']);
             }
        });
      
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    public function authenticate(Request $request){
      
        $credentials = $request->only('code', 'device_locator');

        $otp = trim($request->code);
        $deviceLocator = trim($request->device_locator);
        $expiry = config('constants.otpexpire'); //defined in constants.php file
      
        $expiryTime = date('Y-m-d H:i:s',strtotime("-".$expiry));
        $checkOtp = Otp::where('device_locator',$deviceLocator)->where('used', 0)->where('code',$otp)->where('created_at', '>=', $expiryTime)->count();
        
        if($checkOtp > 0){
            Otp::where('code',$otp)->update(['used' => 1]);
           
            
            //token generation
            // try {
            //     if (! $token = JWTAuth::attempt($credentials)) {
            //         return response()->json([
            //             'success' => false,
            //             'message' => 'Login credentials are invalid.',
            //         ], 400);
            //     }
            // } catch (JWTException $e) {
            // return $credentials;
            //     return response()->json([
            //             'success' => false,
            //             'message' => 'Could not create token.',
            //         ], 500);
            // }

            return response([
                'status' => 'success',
                'message' => 'Verified Otp'
            ], 200);


        }
        else{ 
            return response([
                'status' => 'fail',
                'message' => 'Invalid Otp'
            ], 200);
        }
    }
    
}
