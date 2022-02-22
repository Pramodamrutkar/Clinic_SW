<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Database\QueryException;
use App\Models\CasheAppModel;
use DB;
use Exception;

class CommunicationBgServiceModel extends Model
{
    use HasFactory;
    protected $table = "communication_cronjob";
    protected $primaryKey = "id";
    public $timestamps = true;

    public function getCommDetails($request){
       try{
            set_time_limit(0);
            //$commData = DB::table('comm_types')
            //->join('sms_email_template', 'comm_types.comm_id', '=', 'sms_email_template.comm_type_id')
            //->where('comm_locator','=','EMAIL')->get();

            $this->sendEmailCompleteLoanApplication($request);
            $this->sendEmailCompleteReminderData($request);
            $this->sendEmailSmsOnApplyLoanOnly($request);
            echo "script executed";
        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            //$errolog = new ErrorLogModel();
            //return $errolog->genericMsg();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            //$errolog = new ErrorLogModel();
            //return $errolog->genericMsg();
        }
    }

    public function saveCommunicationinCronJob($data){
            $cmg = new CommunicationBgServiceModel();
            $cmg->comm_type_id = $data['comm_type_id'];
            $cmg->user_prospect_uid = $data['user_prospect_uid'];
            $cmg->user_creditapp_uid = $data['user_creditapp_uid'];
            $cmg->email_sent_count = $data['email_sent_count'] ?? 0;
            $cmg->sms_sent_count = $data['sms_sent_count'] ?? 0;
            $cmg->device_locator = $data["device_locator"];
            $cmg->save();
    }

    public function sendEmailCompleteLoanApplication($request){
        $commsData = DB::table('comm_types')
        ->join('sms_email_template', 'comm_types.comm_id', '=', 'sms_email_template.comm_type_id')
        ->where('comm_type','=','COMM7')->get();

        $reminderData = DB::select("SELECT CASE WHEN date(cp.updated_at) >= date(cp.created_at) THEN TIMESTAMPDIFF(MINUTE,cp.updated_at,CURRENT_TIMESTAMP) WHEN date(cp.created_at) >= date(cp.updated_at) THEN  TIMESTAMPDIFF(MINUTE,cp.created_at,CURRENT_TIMESTAMP) END AS timeinMinutes, ct.timeframe as timeframe, cp.first_name as firstname, cp.last_name as lastname,cp.email as email, cp.mobile_phone_number as mobilenumber, cp.created_at as credit_prospect_created, cp.updated_at as credit_prospect_updated, cp.credituid as credit_prospect_id, ca.creditapp_uuid as credit_app_id, ca.submitted as credit_app_submitted, cc.* FROM credit_prospect as cp LEFT JOIN credit_app as ca on ca.creditapp_uuid=cp.inflight_credit_app_id left join communication_cronjob as cc on cc.user_prospect_uid=cp.credituid LEFT JOIN comm_types as ct ON ct.comm_id=cc.comm_type_id LEFT JOIN otp ON otp.context = cp.credituid where  date(cp.created_at) = curdate() BETWEEN DATE_SUB(curdate(), INTERVAL 2 DAY) AND curdate() or date(cp.updated_at) BETWEEN DATE_SUB(curdate(), INTERVAL 2 DAY) AND curdate() AND ca.submitted is null and cp.is_remind_me_later = 0 and otp.used is not null");

       //$timeFrameArray = array_column(json_decode(json_encode($commsData),true),"timeframe");

        for($i = 0; $i < count($commsData); $i++){
            for ($j=0; $j < count($reminderData); $j++) {
                if(($reminderData[$j]->credit_app_submitted == 0) || ($reminderData[$j]->credit_app_submitted == null)) {
                     // print_r($reminderData[$j]->timeinMinutes."==".$commsData[$i]->timeframe);
                        if($reminderData[$j]->timeinMinutes >= $commsData[$i]->timeframe){
                            if($reminderData[$j]->timeframe != $commsData[$i]->timeframe){
                               if(!empty($reminderData[$j]->email)){
                                    $recordExist = $this->checkRecordExist($commsData[$i]->comm_type_id,$reminderData[$j]->credit_prospect_id);
                                    if($recordExist == false){
                                        $prospectId = $reminderData[$j]->credit_prospect_id;
                                        if($commsData[$i]->comm_locator == "EMAIL"){
                                            $email = $reminderData[$j]->email;
                                            $messagePage = "blankTemplate";
                                            $firstname = $reminderData[$j]->firstname ?? "Customer";
                                            $urlBase = $request->getHost();
                                            $bodyStr = $commsData[$i]->body;
                                            $bodyStr = str_replace('{FIRSTNAME}',$firstname,$bodyStr);
                                            $bodyStr = str_replace('PROSPECTID',$prospectId,$bodyStr);
                                            $bodyStr = str_replace('URLBASE',$urlBase,$bodyStr);
                                            $body = new HtmlString($bodyStr);
                                            $subject = $commsData[$i]->subject;
                                            $data = array("toEmail" => $email, "subject" => $subject,"msg" =>$body);
                                            CasheAppModel::sendTemplateEmails($messagePage,$data);
                                            $storeData['device_locator'] = "EMAIL";
                                        }
                                        if($commsData[$i]->comm_locator == "SMS"){
                                            if(!empty($reminderData[$j]->mobilenumber)){
                                                $smsbodyStr = $commsData[$i]->body;
                                                $templateId = $commsData[$i]->template_no;
                                                $urlBase2 = $request->getHost();
                                                $urlResource = $urlBase2."/transfer/".$prospectId;
                                                $smsbodyStr = str_replace('{#var#}',$urlResource,$smsbodyStr);
                                                $msg = $smsbodyStr;
                                                $this->sendMessagewithTemplateId($reminderData[$j]->mobilenumber,$msg,$templateId);
                                                ErrorLogModel::LogError($status = 200, 200, "sms sent1 com7");
                                                $storeData['device_locator'] = "SMS";
                                            }
                                        }
                                        $storeData['comm_type_id'] = $commsData[$i]->comm_type_id;
                                        $storeData['user_prospect_uid'] = $prospectId;
                                        $storeData['user_creditapp_uid'] = $reminderData[$j]->credit_app_id;
                                        $storeData['email_sent_count'] = $reminderData[$j]->email_sent_count + 1 ?? 1;
                                        $this->saveCommunicationinCronJob($storeData);
                                    }
                                }
                            }
                        }
                    }
            }
        }

    }

    public function checkRecordExist($commTypeId,$user_prospect_uid){
        $result = CommunicationBgServiceModel::where("comm_type_id",$commTypeId)->where("user_prospect_uid",$user_prospect_uid)->count();
        if($result > 0){
            return true;
        }else{
            return false;
        }
    }

    public function sendEmailCompleteReminderData($request){
        $commsData = DB::table('comm_types')
        ->join('sms_email_template', 'comm_types.comm_id', '=', 'sms_email_template.comm_type_id')
        ->where('comm_type','=','COMM18')->get();

        $reminderData = DB::select("SELECT CASE  WHEN date(cp.updated_at) >= date(cp.created_at) THEN TIMESTAMPDIFF(MINUTE,cp.updated_at,CURRENT_TIMESTAMP) WHEN date(cp.created_at) >= date(cp.updated_at) THEN  TIMESTAMPDIFF(MINUTE,cp.created_at,CURRENT_TIMESTAMP) END AS timeinMinutes, ct.timeframe as timeframe,cp.is_remind_me_later as is_remind_me_later, cp.mobile_phone_number as mobilenumber, cp.first_name as firstname, cp.last_name as lastname,cp.email as email, cp.created_at as credit_prospect_created, cp.updated_at as credit_prospect_updated, cp.credituid as credit_prospect_id, cc.* FROM credit_prospect as cp LEFT JOIN communication_cronjob as cc on cc.user_prospect_uid=cp.credituid LEFT JOIN comm_types as ct ON ct.comm_id = cc.comm_type_id LEFT JOIN otp ON otp.context=cp.credituid where date(cp.created_at) = curdate() BETWEEN DATE_SUB(curdate(), INTERVAL 5 DAY) AND curdate() or date(cp.updated_at) BETWEEN DATE_SUB(curdate(), INTERVAL 5 DAY) AND curdate() AND cp.is_remind_me_later=1 and otp.used is null");



        for($i = 0; $i < count($commsData); $i++){
            for ($j=0; $j < count($reminderData); $j++) {

                if($reminderData[$j]->is_remind_me_later == 1) {
                    if($reminderData[$j]->timeinMinutes >= $commsData[$i]->timeframe){

                        if($reminderData[$j]->timeframe != $commsData[$i]->timeframe){

                            if(!empty($reminderData[$j]->email)){
                                $recordExist = $this->checkRecordExist($commsData[$i]->comm_type_id,$reminderData[$j]->credit_prospect_id);
                                if($recordExist == false){
                                    $prospectId = $reminderData[$j]->credit_prospect_id;
                                    if($commsData[$i]->comm_locator == "EMAIL"){
                                        $email = $reminderData[$j]->email;
                                        $messagePage = "blankTemplate";
                                        $firstname = $reminderData[$j]->firstname ?? "Customer";
                                        $urlBase = $request->getHost();
                                        $bodyStr = $commsData[$i]->body;
                                        $bodyStr = str_replace('{FIRSTNAME}',$firstname,$bodyStr);
                                        $bodyStr = str_replace('PROSPECTID',$prospectId,$bodyStr);
                                        $bodyStr = str_replace('URLBASE',$urlBase,$bodyStr);
                                        $body = new HtmlString($bodyStr);
                                        $subject = $commsData[$i]->subject;
                                        $data = array("toEmail" => $email, "subject" => $subject,"msg" =>$body);
                                        CasheAppModel::sendTemplateEmails($messagePage,$data);
                                        $storeData['device_locator'] = "EMAIL";
                                    }
                                    if($commsData[$i]->comm_locator == "SMS"){
                                        if(!empty($reminderData[$j]->mobilenumber)){
                                            $smsbodyStr = $commsData[$i]->body;
                                            $templateId = $commsData[$i]->template_no;
                                            $urlBase1 = $request->getHost();
                                            $urlResource = $urlBase1."/transfer/".$prospectId;
                                            $smsbodyStr = str_replace('{#var#}',$urlResource,$smsbodyStr);
                                            $msg = $smsbodyStr;
                                            $this->sendMessagewithTemplateId($reminderData[$j]->mobilenumber,$msg,$templateId);
                                            ErrorLogModel::LogError($status = 200, 200, "sms sent com18");
                                            $storeData['device_locator'] = "SMS";
                                        }
                                    }
                                    $storeData['comm_type_id'] = $commsData[$i]->comm_type_id;
                                    $storeData['user_prospect_uid'] = $prospectId;
                                    $storeData['user_creditapp_uid'] = 0;
                                    $storeData['email_sent_count'] = $reminderData[$j]->email_sent_count + 1 ?? 1;
                                    $this->saveCommunicationinCronJob($storeData);
                                }
                            }
                        }
                    }
                }
            }

        }
    }


    public function sendEmailSmsOnApplyLoanOnly($request){
        $commIdArray = array(8,9,11,12);
        $commsData = DB::table('comm_types')
        ->join('sms_email_template', 'comm_types.comm_id', '=', 'sms_email_template.comm_type_id')
        ->where('comm_type','=','COMM18')
        ->whereIn('comm_types.comm_id',$commIdArray)->get();
        //comms to send email/sms only when user click on apply on loan but didn't proceed for further process.
        $reminderData = DB::select("SELECT CASE WHEN date(cp.updated_at) >= date(cp.created_at) THEN TIMESTAMPDIFF(MINUTE,cp.updated_at,CURRENT_TIMESTAMP) WHEN date(cp.created_at) >= date(cp.updated_at) THEN  TIMESTAMPDIFF(MINUTE,cp.created_at,CURRENT_TIMESTAMP) END AS timeinMinutes, ct.timeframe as timeframe, cp.first_name as firstname, cp.last_name as lastname,cp.email as email, cp.mobile_phone_number as mobilenumber, cp.created_at as credit_prospect_created, cp.updated_at as credit_prospect_updated, cp.credituid as credit_prospect_id, ca.creditapp_uuid as credit_app_id, ca.submitted as credit_app_submitted,cp.is_remind_me_later as is_remind_me_later, cc.* FROM credit_prospect as cp LEFT JOIN credit_app as ca on ca.creditapp_uuid=cp.inflight_credit_app_id left join communication_cronjob as cc on cc.user_prospect_uid=cp.credituid LEFT JOIN comm_types as ct ON ct.comm_id=cc.comm_type_id where  date(cp.created_at) = curdate() BETWEEN DATE_SUB(curdate(), INTERVAL 2 DAY) AND curdate() or date(cp.updated_at) BETWEEN DATE_SUB(curdate(), INTERVAL 2 DAY) AND curdate() AND ca.submitted is null and ca.creditapp_uuid is null and cp.is_remind_me_later=0 and cp.is_editing = 1");

        if(!empty($reminderData)){
            for($i = 0; $i < count($commsData); $i++){
                for ($j=0; $j < count($reminderData); $j++) {
                    if($reminderData[$j]->is_remind_me_later == 0) {
                        if($reminderData[$j]->timeinMinutes >= $commsData[$i]->timeframe){

                            if($reminderData[$j]->timeframe != $commsData[$i]->timeframe){

                                if(!empty($reminderData[$j]->email)){
                                    $recordExist = $this->checkRecordExist($commsData[$i]->comm_type_id,$reminderData[$j]->credit_prospect_id);
                                    if($recordExist == false){
                                        $prospectId = $reminderData[$j]->credit_prospect_id;
                                        if($commsData[$i]->comm_locator == "EMAIL"){
                                            $email = $reminderData[$j]->email;
                                            $messagePage = "blankTemplate";
                                            $firstname = $reminderData[$j]->firstname ?? "Customer";
                                            $urlBase = $request->getHost();
                                            $bodyStr = $commsData[$i]->body;
                                            $bodyStr = str_replace('{FIRSTNAME}',$firstname,$bodyStr);
                                            $bodyStr = str_replace('PROSPECTID',$prospectId,$bodyStr);
                                            $bodyStr = str_replace('URLBASE',$urlBase,$bodyStr);
                                            $body = new HtmlString($bodyStr);
                                            $subject = $commsData[$i]->subject;
                                            $data = array("toEmail" => $email, "subject" => $subject,"msg" =>$body);
                                            CasheAppModel::sendTemplateEmails($messagePage,$data);
                                            $storeData['device_locator'] = "EMAIL";
                                        }
                                        if($commsData[$i]->comm_locator == "SMS"){
                                            if(!empty($reminderData[$j]->mobilenumber)){
                                                $smsbodyStr = $commsData[$i]->body;
                                                $templateId = $commsData[$i]->template_no;
                                                $urlBase1 = $request->getHost();
                                                $urlResource = $urlBase1."/transfer/".$prospectId;
                                                $smsbodyStr = str_replace('{#var#}',$urlResource,$smsbodyStr);
                                                $msg = $smsbodyStr;
                                                $this->sendMessagewithTemplateId($reminderData[$j]->mobilenumber,$msg,$templateId);
                                                ErrorLogModel::LogError($status = 200, 200, "sms sent for comm 18 2nd & 3rd");
                                                $storeData['device_locator'] = "SMS";
                                            }
                                        }
                                        $storeData['comm_type_id'] = $commsData[$i]->comm_type_id;
                                        $storeData['user_prospect_uid'] = $prospectId;
                                        $storeData['user_creditapp_uid'] = 0;
                                        $storeData['email_sent_count'] = $reminderData[$j]->email_sent_count + 1 ?? 1;
                                        $this->saveCommunicationinCronJob($storeData);
                                    }
                                }
                            }
                        }
                    }
                }

            }
        }
    }



    public function sendMessagewithTemplateId($mobileNo, $message,$templateId)
    {
        try {
            $senderId = config('constants.sender');
            $authKey = config('constants.authkey');
            $peId = config('constants.pe_idEntityId');
            $str = "authkey=" . $authKey . "&mobile=" . $mobileNo . "&country_code=91&sms=" . $message . "&sender=" . $senderId . "&pe_id=" . $peId . "&template_id=" . $templateId;
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
                ErrorLogModel::LogError($status = 500, 400, "Error in msg sending");
                return 0;
            } else {
                ErrorLogModel::LogError($status = 500, 400, "No Error in msg sending");
                return 1;
            }
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            echo ErrorLogModel::genericMessage();
        }
    }
}

