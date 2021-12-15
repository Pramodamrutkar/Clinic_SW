<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreditProspect extends Model
{
    use HasFactory;

    //protected $fillable = ['issend_toemail','channel_id','email','mobile_phone_number','mobile_phone_code'];
    protected $guarded = ['issend_toemail'];
    protected $table = "credit_prospect";
    
    protected $primaryKey = "user_id";
    
    public $timestamps = true;

    public function saveBasicsdetails($request){
        $creditProspectUpdate = CreditProspect::where('mobile_phone_number',$request->mobile_phone_number)->where('email',$request->email)->first();
      
        if(!empty($creditProspectUpdate)){
            $creditProspectUpdate->credituid = $creditProspectUpdate->credituid;
            $creditProspectUpdate->channel_id = $request['channel_id'];
            $creditProspectUpdate->email = $request['email'];
            $creditProspectUpdate->mobile_phone_code = $request['mobile_phone_code'];
            $creditProspectUpdate->mobile_phone_number = $request['mobile_phone_number'];
            $creditProspectUpdate->role_id = 1;
            if($creditProspectUpdate->save()){
                return $creditProspectUpdate->user_id;
            }else{
                return false;
            }
        }else{
            $this->credituid = (string) Str::uuid();
            $this->channel_id = $request['channel_id'];
            $this->email = $request['email'];
            $this->mobile_phone_code = $request['mobile_phone_code'];
            $this->mobile_phone_number = $request['mobile_phone_number'];
            $this->role_id = 1;
            if($this->save()){
                return $this->user_id;
            }else{
                return false;
            }
        }
        
    }   

    public function saveCreditProspectData($request){
        
        $creditProspectData = CreditProspect::where('mobile_phone_number',$request->mobile_phone_number)->where('email',$request->email)->first();
        $creditProspectData->first_name = $request['first_name'];
        $creditProspectData->middle_name = $request['middle_name'];
        $creditProspectData->last_name = $request['last_name'];
        $creditProspectData->birth_date = $request['birth_date'];
        $creditProspectData->tin = $request['tin'];
        $creditProspectData->credit_amount = $request['credit_amount'];
        if($creditProspectData->save()){
            return $creditProspectData->credituid;	
        }else{
            return false;
        }
    }
}
