<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\CreditApp;
class UpwardsAppModel extends Model
{
    use HasFactory;

    protected $table = "upwards_app";
    protected $primaryKey = "upwardapp_id";
    public $timestamps = true;

    public function saveUpwardsDetails($request,$app_id){
        $creditAppIdCount = CreditApp::where('creditapp_uuid',$app_id)->count();
        if($creditAppIdCount != 1){
            return Response([
                'status' => 'fail',
                'message' => 'Invalid AppID'
            ],400);
        }
        $this->creditapp_uid = trim($app_id); 
        $this->upwardapp_uid = (string) Str::uuid(); 
        $this->residency_type = $request['residency_type'];
        $this->gender = $request['gender'];
        $this->current_residency_stay_category = $request['current_residency_stay_category'];
        $this->company = $request['company'];
        $this->salary_payment_mode = $request['salary_payment_mode'];
        $this->profession_type = $request['profession_type'];
        $this->current_employment_tenure = $request['current_employment_tenure'];
        $this->total_work_experience = $request['total_work_experience'];
        $this->bank_account_number = $request['bank_account_number'];
        $this->bank_account_holder_name = $request['bank_account_holder_name'];
        $this->ifsc = $request['ifsc'];
        $this->loan_purpose = $request['loan_purpose'];
        $this->lender_customer_id = $request['lender_customer_id'];
        $this->lender_system_id = $request['lender_system_id'];
        $this->Iframe_url = $request['Iframe_url'];
        $this->amount = $request['amount'];
        $this->emi = $request['emi'];
        $this->annual_interest_rate = $request['annual_interest_rate'];
        $this->term_months = $request['term_months'];
        //$this->mis_status = $request['mis_status'];
        $this->merchant_tracking_id = $request['merchant_tracking_id'];
        $this->leder_created = $request['leder_created'];
        $this->processing_fees = $request['processing_fees'];
        if($this->save()){
            return Response([
                'status' => 'true',
                'message' => 'saved data successfully!',
                'upwardapp_uid' => $this->upwardapp_uid
            ],200);
        }else{
            return Response([
                'status' => 'false',
                'message' => 'Something went wrong'
            ],400);
        }
    }

    
}
