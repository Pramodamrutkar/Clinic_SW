<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests;

use Mail;

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
    
            if(is_numeric($mobileNo)){

                $application_name = config('constants.application_name');
                $senderId = config('constants.sender');
        
                $message = 'OTP for '.$application_name.' Registration is : '.$otp;
                $flag = $this->sendMessage($senderId, $mobileNo, $message,$phoneCode);
                
                if($flag == 0){
                    return ' { "status" : "fail" , "message" : "Some Error occured While sending OTP" } ';	
                }
                
                // $now = date("Y-m-d H:i:s");
                // $insert = new \App\OtpM;
                // $insert->contact = $contact;
                // $insert->otp = $otp;
                // $insert->createdOn = $now;
                // $insert->save();
                return ' { "status" : "success" , "message" : "OTP has been sent" } ';	
            }else{
                $messagePage = "mail"; // Create message in view.
                $subject = "Your CreditLinks One-Time Password";
                $this->sendEmail($messagePage,$emailId,$subject,$otp);
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

}
