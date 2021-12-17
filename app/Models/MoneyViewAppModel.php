<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\CreditApp;

class MoneyViewAppModel extends Model
{
    use HasFactory;

    protected $table = "moneyview_app";
    protected $primaryKey = "moneyview_id";
    public $timestamps = true;

    public function saveMoneyView($request,$app_id){
        $creditAppIdCount = CreditApp::where('creditapp_uuid',$app_id)->count();
        if($creditAppIdCount != 1){
            return Response([
                'status' => 'fail',
                'message' => 'Invalid AppID'
            ],400);
        }
        $this->moneyview_uid = (string) Str::uuid();
        $this->creditapp_uid = trim($app_id);
        $this->residency_type = $request['residency_type'];
        $this->gender = $request['gender'];
        $this->educational_level = $request['educational_level'];
        $this->salary_payment_mode = $request['salary_payment_mode'];
        $this->prefer_net_banking = $request['prefer_net_banking'];
        $this->term_of_use = $request['term_of_use'];
        $this->lender_system_id = $request['lender_system_id'];
        $this->journey_url = $request['journey_url'];
        $this->amount = $request['amount'];
        $this->emi = $request['emi'];
        $this->annual_interest_rate = $request['annual_interest_rate'];
        $this->term_months = $request['term_months'];
        $this->mis_status = $request['mis_status'];
        $this->merchant_tracking_id = $request['merchant_tracking_id'];
        $this->lender_created = $request['lender_created'];
        if($this->save()){
            return Response([
                'status' => 'true',
                'message' => 'saved data successfully!',
                'moneyview_uid' => $this->moneyview_uid
            ],200);
        }else{
            return Response([
                'status' => 'false',
                'message' => 'Something went wrong'
            ],400);
        }
    }

}
