<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\CreditProspect;

class CreditApp extends Model
{
    use HasFactory;

    protected $table = "credit_app";
    
    protected $primaryKey = "app_id";
    
    public $timestamps = true;


    public function savePersonalInformationiInApp($request){

        $creditProspect = new CreditProspect();
        $creditProspectUuid = $creditProspect->saveCreditProspectData($request);
        $this->creditprospect_uuid = $creditProspectUuid;
        $this->creditapp_uuid = (string) Str::uuid();
        $this->first_name = $request['first_name'];
        $this->middle_name =$request['middle_name'];
        $this->last_name = $request['last_name'];
        $this->birth_date = $request['birth_date'];
        $this->tin = $request['tin'];
        $this->email = $request['email'];
        $this->mobile_phone_number = $request['mobile_phone_number'];
        $this->address1 = $request['address1'];
        $this->address2 = $request['address2'];
        $this->address3 = $request['address3'];
        $this->city = $request['city'];
        $this->state = $request['state'];
        $this->postal_code = $request['postal_code'];
        $this->country = $request['country'];
        $this->employment_status_code = $request['employment_status_code'];
        $this->annual_income = $request['annual_income'];
        $this->credit_amount = $request['credit_amount'];
        $this->currency_code = $request['currency_code'];
        $this->marketing_consent = $request['marketing_consent'];
        $this->allow_emails = $request['allow_emails'];
        $this->allow_sms = $request['allow_sms'];
        $this->submitted = $request['submitted'];
        $this->readings = $request['readings'];
        $this->autosaved = $request['autosaved'];
        $this->attempts = $request['attempts'];
        $this->when_last_attempted = $request['when_last_attempted'];
        if($this->save()){
            return response([
                'status' => 'success',
                'message' => 'Added Record Successfully!',
                'app_id' => $this->app_id
            ],201);
            
        }else{
            return response([
                'status' => 'fail',
                'message' => 'something went wrong!'
            ],200);
        }
    }
}
