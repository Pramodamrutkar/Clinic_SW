<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;


class ConfigEmailsModel extends Model
{
    use HasFactory;
    protected $table = "config_emails";
    protected $primaryKey = "id";
    public $timestamps = true;

    public static function getEmailIds($emailType){
        try{
            $result = ConfigEmailsModel::where("email_type",$emailType)->first();
            if(empty($result)){
                return "";
            }else{
                return $result["emails"];
            }
        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            echo ErrorLogModel::genericMessage();
        } 
    }
}
