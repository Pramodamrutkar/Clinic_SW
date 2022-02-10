<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Exception;

class SmsEmailTemplateModel extends Model
{
    use HasFactory;
    protected $table = "sms_email_template";
    protected $primaryKey = "id";
    public $timestamps = true;


    public function getSmsOrEmailTemplateBasedOnId($id){
        try {
            $smsEmailData = SmsEmailTemplateModel::where("id",$id)->first();
            if(!empty($smsEmailData)){
                return $smsEmailData;
            }else{
                return "";
            }
        }  catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message." = Get SMS EMAIL TEMPLATE");
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message."= Get SMS EMAIL TEMPLATE");
        }
    }

}
