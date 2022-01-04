<?php

namespace App\Models;

use Facade\FlareClient\Http\Response;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Exception;

class CreditProspect extends Model
{
    use HasFactory;

    protected $user;

    protected $guarded = ['issend_toemail'];

    protected $table = "credit_prospect";

    protected $primaryKey = "user_id";

    public $timestamps = true;

    // public function saveBasicsdetails($request){
    //     $creditProspectUpdate = CreditProspect::where('mobile_phone_number',$request->mobile_phone_number)->where('email',$request->email)->first();

    //     if(!empty($creditProspectUpdate)){
    //         $creditProspectUpdate->credituid = $creditProspectUpdate->credituid;
    //         $creditProspectUpdate->channel_id = $request['channel_id'];
    //         $creditProspectUpdate->email = $request['email'];
    //         $creditProspectUpdate->mobile_phone_code = $request['mobile_phone_code'];
    //         $creditProspectUpdate->mobile_phone_number = $request['mobile_phone_number'];
    //         $creditProspectUpdate->role_id = 1;
    //         if($creditProspectUpdate->save()){
    //             return $creditProspectUpdate->user_id;
    //         }else{
    //             return false;
    //         }
    //     }else{
    //         $this->credituid = (string) Str::uuid();
    //         $this->channel_id = $request['channel_id'];
    //         $this->email = $request['email'];
    //         $this->mobile_phone_code = $request['mobile_phone_code'];
    //         $this->mobile_phone_number = $request['mobile_phone_number'];
    //         $this->role_id = 1;
    //         if($this->save()){
    //             return $this->user_id;
    //         }else{
    //             return false;
    //         }
    //     }

    // }   

    public function retriveUseronAppID($appId)
    {
        try {
            $app_ID = trim($appId);
            $creditProspectData = CreditProspect::where('credituid', trim($app_ID))->first();
            if (empty($creditProspectData)) {
                return response([
                    'success' => 'false',
                    'message' => 'Invalid Application ID'
                ], 400);
            } else {
                return response([
                    'success' => 'true',
                    'message' => 'Data found',
                    'data' => $creditProspectData
                ], 200);
            }
        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            echo ErrorLogModel::genericMessage();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            echo ErrorLogModel::genericMessage();
        }
    }
}
