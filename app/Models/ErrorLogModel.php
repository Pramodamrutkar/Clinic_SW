<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLogModel extends Model
{
    use HasFactory;

    protected $table = "error_log";
    protected $primaryKey = "id";
    public $timestamps = true;

    public static function LogError($status = "",$code="",$details="",$created_by=""){
        $errorLogModel = new static;
        $errorLogModel->status = trim($status);
        $errorLogModel->code = trim($code);
        $errorLogModel->details = trim($details);
        $errorLogModel->created_by = trim($created_by);
        $errorLogModel->save();
    }

    public static function genericMessage($case = ''){
        $arr = array(
            'success' => 'false',
            'message' => 'Unfortunately, we are not able to proceed with your request. Please try again.'
         ); 
        return json_encode($arr);
    }
}
