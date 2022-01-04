<?php

namespace App\Models;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\CreditApp;
use App\Models\CasheAppModel;
use App\Models\UpwardsAppModel;
use DB;
use Exception;

class MoneyViewAppModel extends Model
{
    use HasFactory;

    protected $table = "moneyview_app";
    protected $primaryKey = "moneyview_id";
    public $timestamps = true;

    public function saveMoneyView($request,$app_id){
        try{
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
            $this->merchant_tracking_id = $request['merchant_tracking_id'];
            $this->lender_created = $request['lender_created'];
            $this->processing_fees = $request['processing_fees'];
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
        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message,$app_id);
            echo ErrorLogModel::genericMessage();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message,$app_id);
            echo ErrorLogModel::genericMessage();
        }
    }

    public function listOfferChart($app_id){
        try{
            if(empty($app_id)){
                return Response([
                    'status' => 'false',
                    'message' => 'Empty Application ID'
                ],400);
            }
            $upwardlenderSystemId = '';
            $UpwardsAppModel = UpwardsAppModel::select('*')->where('creditapp_uid',$app_id)->first();
    
            if(!empty($UpwardsAppModel)){
                $upwardlenderSystemId = $UpwardsAppModel['lender_system_id'];
                $lenderCustomerId = $UpwardsAppModel['lender_customer_id'];
            }
            if(!empty($upwardlenderSystemId)){
                $upwardsApp = new UpwardsAppModel();
                $upwardStatus = $upwardsApp->getUpwardStatus($upwardlenderSystemId,$lenderCustomerId);
                //$upwardModel = UpwardsAppModel::where("creditapp_uid",$app_id)->first();
                $UpwardsAppModel->mis_status = $upwardStatus ?? "";
                $UpwardsAppModel->save();
            }

            $statusOnOff = ExternalConnectorsModel::externalConnects("CASHESTATUS");
            if($statusOnOff == 1){
                $cashelenderSystemId = 0;
                $casheAppModel = CasheAppModel::select('*')->where('creditapp_uid',$app_id)->first();
                
                if(!empty($casheAppModel)){
                    $cashelenderSystemId = $casheAppModel['lender_system_id'];
                    $casheApp = new CasheAppModel();
                    $responseCasheStatus = $casheApp->getCacheStatus($cashelenderSystemId);

                    if(!empty($responseCasheStatus)){
                        $casheAppModel->mis_status = $responseCasheStatus;
                        $casheAppModel->save();
                    }
                    
                }
            }
            
            // $offerData = DB::table('moneyview_app')
            // ->leftJoin('upwards_app', 'moneyview_app.creditapp_uid', '=' ,'upwards_app.creditapp_uid')
            // ->leftJoin('cashe_app','cashe_app.creditapp_uid', '=', 'moneyview_app.creditapp_uid')
            // ->where('moneyview_app.creditapp_uid','=',trim($app_id))
            // ->select('moneyview_app.lender_system_id as lender','moneyview_app.amount as amount','moneyview_app.mis_status as status','upwards_app.lender_system_id as upwardsLender','upwards_app.amount as upwardsAmount','upwards_app.mis_status as upwardsStatus','cashe_app.lender_system_id as casheLender','cashe_app.amount as casheAmount','cashe_app.mis_status as casheStatus')
            // ->first();

            $upwardsApp = UpwardsAppModel::select('upwards_app.lender_name as lender','upwards_app.amount as amount','upwards_app.mis_status as status','upwards_app.Iframe_url as Iframe_url')->where('creditapp_uid',$app_id)->first();
            $moneyView = MoneyViewAppModel::select('moneyview_app.lender_name as lender','moneyview_app.amount as amount','moneyview_app.mis_status as status','moneyview_app.journey_url as Iframe_url')->where('creditapp_uid',$app_id)->first();
            $casheApp = CasheAppModel::select('cashe_app.lender_name as lender','cashe_app.amount as amount','cashe_app.mis_status as status')->where('creditapp_uid',$app_id)->first();
        
            $offerData = array();
            if(!empty($upwardsApp)){
                $offerData[] = $upwardsApp;
            }
            if(!empty($moneyView)){
                $offerData[] = $moneyView;
            }
            if(!empty($casheApp)){
                $offerData[] = $casheApp;
            }
            
            if(!empty($offerData)){
                return Response([
                    'status' => 'true',
                    'message' => 'Data found',
                    'data' => $offerData
                ],200);
            }else{
                return Response([
                    'status' => 'false',
                    'message' => 'No Data'
                ],204);
            }

        } catch (QueryException $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message,$app_id);
            echo ErrorLogModel::genericMessage();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message,$app_id);
            echo ErrorLogModel::genericMessage();
        }
    }

    
}
