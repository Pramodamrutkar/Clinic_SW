<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreditProspect extends Model
{
    use HasFactory;

    protected $fillable = ['issend_toemail','channel_id','email','mobile_phone_number','mobile_phone_code'];

    protected $table = "credit_prospect";
    
    protected $primaryKey = "user_id";
    
    public $timestamps = true;

    public function saveBasicsdetails($request){
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
