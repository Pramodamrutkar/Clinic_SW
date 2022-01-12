<?php

namespace App\Http\Controllers;

use App\Models\CasheAppModel;
use Illuminate\Http\Request;
#use Illuminate\support\Facades\DB;
use App\Models\FormulaBuilderEngine;
use App\Models\Locations;
use App\Models\Offers;
use App\Models\OfferWeightage;
use App\Models\CreditApp;
use App\Models\UpwardsAppModel;
use App\Models\ExternalConnectorsModel;
use App\Models\ErrorLogModel;
use DateTime;
use DB;
class FormulaBuilderEngineController extends Controller
{
    //
	/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function searchOffer(Request $request)
    {		
		$creditAppUUID = trim($request->uuID);
		
		$CreditAppData = CreditApp::where('creditapp_uuid', $creditAppUUID )->first();
      
		$monthlyIncome = $CreditAppData->monthly_income;
		$birthDate = $CreditAppData->birth_date;
		$postalCode = $CreditAppData->postal_code;
		$employeementStatus = $CreditAppData->employment_status_code;
		$mobilePhoneNumber = $CreditAppData->mobile_phone_number;
		
		$emailId = $CreditAppData->email;
		$panId = $CreditAppData->tin;

		$from = new DateTime($birthDate);
		$to   = new DateTime('today');
		$age  = $from->diff($to)->y;
		
	
        $locationData = Locations::where('postal_code', $postalCode )->first();
		
		
		$offerName = DB::select('SELECT offer_name,lender_name FROM `formula_builder_engine` where (offer_key = "amount" AND offer_min_number <= "'.$monthlyIncome.'" AND offer_max_number >= "'.$monthlyIncome.'" AND status = 1)');		


		foreach($offerName as $v1)
		{
			$new_arr_1[] = $v1->offer_name;
		}

		if(empty($new_arr_1))
		{
			return [];
		}

		$offerName1 = DB::table('formula_builder_engine')
					->select('offer_name','lender_name')
					->whereIn('offer_name',$new_arr_1)
					->where(['offer_key'=>'City_Tier','offer_number'=> $locationData->city_tier, 'status'=>'1'])					
					->get();
					
		foreach($offerName1 as $v2)
		{
			$new_arr_2[] = $v2->offer_name;
		}

		$offerName2 =DB::table('formula_builder_engine')
			->select('offer_name','lender_name')
			->whereIn('offer_name',$new_arr_2)
			->where('offer_key','age')
			->where('offer_min_number','<=',$age)
			->where('offer_max_number','>=',$age)			
			->get();		

		
		foreach($offerName2 as $v3)
		{
			$new_arr_3[] = $v3->offer_name;
		}	
	
		$offerName3 =DB::table('formula_builder_engine')
			->select('offer_name','lender_name')
			->whereIn('offer_name',$new_arr_3)
			->where(['offer_key'=>'employeement_type','offer_number'=>$employeementStatus,'status'=>'1'])			
			->get();
		
		
		$lendersMainArray = array();
		foreach($offerName3 as $value)
		{
			$new_arr[] = $value->offer_name;
			$lender_name[] = $value->lender_name;
			if($value->lender_name == 'Upwards'){
				$lendersMainArray[$value->lender_name][] = $value->offer_name;
			}else if($value->lender_name == 'MoneyView'){
				$lendersMainArray[$value->lender_name][] = $value->offer_name;
			}else {
				$lendersMainArray[$value->lender_name][] = "CASHe";
			}
		}		
	
		if(empty($new_arr))
		{
			return [];
		}
		else if(!empty($new_arr))
		{	
			
			$statusOnOff = ExternalConnectorsModel::externalConnects('CALLTOUPWARDS');
			if($statusOnOff == 1){
				$upwardModel = new UpwardsAppModel();
				$upwardData = $upwardModel->checkUpwardsEligibility($emailId,$panId);
				if(!empty($upwardData["data"])){
					if($upwardData["data"]["is_eligible"] === true){
						$code = 3900;
						$message = "Upwards: Eligible for the loan";
						ErrorLogModel::LogError($status = 200, $code, $message,$creditAppUUID);
					 }
					if($upwardData["data"]["is_eligible"] === false){
						for($i=0;$i < count($new_arr); $i++){
							if(stripos($new_arr[$i],"upward") === 0){
								unset($new_arr[$i]);
							}
						}
						$code = 3901;
						$message = "Upwards: Not Eligible for the loan";
						ErrorLogModel::LogError($status = 200, $code, $message,$creditAppUUID);	
					}
				}else{
						$code = 3902;
						$message = "Upwards: Empty response from upward eligibiltiy";
						ErrorLogModel::LogError($status = 200, $code, $message,$creditAppUUID);
						for($i=0;$i < count($new_arr); $i++){
							if(stripos($new_arr[$i],"upward") === 0){
								unset($new_arr[$i]);
							}
						}	
				}
			}
	
			$statusOnOff = ExternalConnectorsModel::externalConnects('CHECKCASHEOFFERS');
			if($statusOnOff == 1){
				$casheAppModel = new CasheAppModel();
				$casheOfferArray = $casheAppModel->casheOffers($mobilePhoneNumber,$birthDate,$monthlyIncome);
				if(!empty($casheOfferArray)){
					$lendersMainArray['CASHe'][] = "CASHe";
					$new_arr[] = "CASHe";
					$lender_name[] = "CASHe";
				}
			}
			
			$new_arr = $this->checkIfUserConsumedOffer($creditAppUUID, $new_arr,$lender_name,$lendersMainArray);
		
		}
	
		//DB::connection()->enableQueryLog();
		//dd(DB::getQueryLog());	
		
		$offersData =DB::table('offers')
					->select('*')
					->whereIn('offer_name',$new_arr)
					->get();
					
		$data = array();
		$array1 = json_decode(json_encode($offersData),True);
	
		foreach($array1 as $key => $val)
		{ 
			$weightageData = OfferWeightage::where('lender_name', $val['lender_name'])->first();
			$grantAmount_1 = 0;
			$grantAmount_2 = 0;
			$grantAmount_3 = 0;
			if($val['lender_name'] == "Upwards"){
				$grantAmount_1 = $val['offer_grant_amount_1'] * $monthlyIncome;
				$grantAmount_2 = $val['offer_grant_amount_2'] * $monthlyIncome;
				$grantAmount_3 = $val['offer_grant_amount_3'] * $monthlyIncome;	
			}else if($val['lender_name'] == "MoneyView"){
				$grantAmount_1 = $val['offer_grant_amount_1'] * $monthlyIncome / 100;
				$grantAmount_2 = $val['offer_grant_amount_2'] * $monthlyIncome / 100;
				$grantAmount_3 = $val['offer_grant_amount_3'] * $monthlyIncome / 100;
			}else{
				$grantAmount_1 = $val['offer_grant_amount_1'] * $monthlyIncome;
				$grantAmount_2 = $val['offer_grant_amount_2'] * $monthlyIncome;
				$grantAmount_3 = $val['offer_grant_amount_3'] * $monthlyIncome;
			}
			
									
			
			$data[] = array('offer_amount_1' => $grantAmount_1);
			$data[] = array('offer_amount_2' => $grantAmount_2);
			$data[] = array('offer_amount_3' => $grantAmount_3);
			
			
			
			//offer 1
			
			$data[]['offer_amount_1'] = $data[0]['offer_amount_1'] * $weightageData->amount_weight/100;
			$data[]['offer_amount_1'] = $val['offer_grant_amount_1'] * $weightageData->roi_weight/100;
			$data[]['offer_amount_1'] = $val['offer_tenure_1'] * $weightageData->tenure_weight/100;
									
			
			//offer2
			$data[]['offer_amount_2'] = $data[1]['offer_amount_2']*$weightageData->amount_weight/100;
			$data[]['offer_amount_2'] = $val['offer_grant_amount_2'] *$weightageData->roi_weight/100;
			$data[]['offer_amount_2'] = $val['offer_tenure_2'] *$weightageData->tenure_weight/100;
									
			//offer3
			$data[]['offer_amount_3'] = $data[2]['offer_amount_3']*$weightageData->amount_weight/100;
			$data[]['offer_amount_3'] = $val['offer_grant_amount_3'] *$weightageData->roi_weight/100;
			$data[]['offer_amount_3'] = $val['offer_tenure_3'] *$weightageData->tenure_weight/100;
									
			//Total ranking
			$data[]['offer_amount_1'] = $data[3]['offer_amount_1'] + $data[4]['offer_amount_1'] + $data[5]['offer_amount_1'];			
			$data[]['offer_amount_2'] = $data[6]['offer_amount_2'] + $data[7]['offer_amount_2'] + $data[8]['offer_amount_2'];				
			$data[]['offer_amount_3'] = $data[9]['offer_amount_3'] + $data[10]['offer_amount_3'] + $data[11]['offer_amount_3'];
			
			$data[]['offer_amount_1'] = $val['offer_month_1'];
			$data[]['offer_amount_2'] =$val['offer_month_2'];
			$data[]['offer_amount_3'] = $val['offer_month_3'];
			
			$data[]['offer_amount_1'] = $val['offer_tenure_1'];
			$data[]['offer_amount_2'] =$val['offer_tenure_2'];
			$data[]['offer_amount_3'] = $val['offer_tenure_3'];
			
			$data[]['offer_amount_1'] = $val['offer_roi_1'];
			$data[]['offer_amount_2'] =$val['offer_roi_2'];
			$data[]['offer_amount_3'] = $val['offer_roi_3'];
			
			$data[]['offer_amount_1'] = $val['offer_pf_1'];
			$data[]['offer_amount_2'] =$val['offer_pf_2'];
			$data[]['offer_amount_3'] = $val['offer_pf_3'];
			
		}
			$newarray = array_chunk($data,27);
			
			$offer =[];
			foreach($newarray as $data)
			{
				$offer_1 = array_column($data,'offer_amount_1',);
				$offer_2 = array_column($data,'offer_amount_2',);
				$offer_3 = array_column($data,'offer_amount_3',);
				array_push($offer,$offer_1);
				array_push($offer,$offer_2);
				array_push($offer,$offer_3);
				
			}
			
			$split_array = array_chunk($offer,3);
			
			$main_array = array();
			foreach($split_array as $key1=> $val)
			{
			
				foreach($val as $key2 => $data_test)
				{							
					
					$push_data = array('offer_amount' => round($data_test[0]));
					$push_data += array('calculated_amount_offer' => $data_test[1]);
					$push_data += array('roi_offer' => $data_test[2]);
					$push_data += array('tenure_offer' => $data_test[3]);
					$push_data += array('total_ranking_offer' => $data_test[4]);
					$push_data += array('offer_month' => $data_test[5]);
					$push_data += array('offer_tenure' => $data_test[6]);
					$push_data += array('offer_roi' => $data_test[7]);
					$push_data += array('offer_pf' => $data_test[8]);
					
					array_push($main_array,$push_data);
					
				}	
			}
			
	
				$Final_array = array_chunk($main_array,3);							
				
				
				$getData = $this->my_array_merge($array1, $Final_array);
		
		//Read code from here and sort array whose total ranking is less
				foreach($getData as $key => $v)
				{	
					$a = $getData[$key]['offers'][0]['total_ranking_offer'];
					$b = $getData[$key]['offers'][1]['total_ranking_offer'];
					$c = $getData[$key]['offers'][2]['total_ranking_offer'];
					
					if($v['lender_name'] == 'MoneyView')
					{

						unset($getData[$key]['offers'][1]);
						unset($getData[$key]['offers'][2]);
						unset($getData[$key]['offer_id']);	
						unset($getData[$key]['offer_month_1']);
						unset($getData[$key]['offer_month_2']);	
						unset($getData[$key]['offer_month_3']);	
						unset($getData[$key]['offer_grant_amount_1']);
						unset($getData[$key]['offer_grant_amount_2']);
						unset($getData[$key]['offer_grant_amount_3']);
						unset($getData[$key]['offer_tenure_1']);
						unset($getData[$key]['offer_tenure_2']);
						unset($getData[$key]['offer_tenure_3']);
						unset($getData[$key]['offer_roi_1']);
						unset($getData[$key]['offer_roi_2']);
						unset($getData[$key]['offer_roi_3']);
						unset($getData[$key]['offer_pf_1']);
						unset($getData[$key]['offer_pf_2']);
						unset($getData[$key]['offer_pf_3']);
							
					}else{
						if ($a >= $b && $a >= $c)
						{
							$getData[$key]['offers'][0]['total_ranking_offer'];
							unset($getData[$key]['offers'][1]);
							unset($getData[$key]['offers'][2]);
							unset($getData[$key]['offer_id']);	
							unset($getData[$key]['offer_month_1']);
							unset($getData[$key]['offer_month_2']);	
							unset($getData[$key]['offer_month_3']);	
							unset($getData[$key]['offer_grant_amount_1']);
							unset($getData[$key]['offer_grant_amount_2']);
							unset($getData[$key]['offer_grant_amount_3']);
							unset($getData[$key]['offer_tenure_1']);
							unset($getData[$key]['offer_tenure_2']);
							unset($getData[$key]['offer_tenure_3']);
							unset($getData[$key]['offer_roi_1']);
							unset($getData[$key]['offer_roi_2']);
							unset($getData[$key]['offer_roi_3']);
							unset($getData[$key]['offer_pf_1']);
							unset($getData[$key]['offer_pf_2']);
							unset($getData[$key]['offer_pf_3']);
							
						}
						else if ($b <= $a && $b <= $c)
						{
							$getData[$key]['offers'][1]['total_ranking_offer'];
							unset($getData[$key]['offers'][0]);
							unset($getData[$key]['offers'][2]);
							unset($getData[$key]['offer_id']);	
							unset($getData[$key]['offer_month_1']);
							unset($getData[$key]['offer_month_2']);	
							unset($getData[$key]['offer_month_3']);	
							unset($getData[$key]['offer_grant_amount_1']);
							unset($getData[$key]['offer_grant_amount_2']);
							unset($getData[$key]['offer_grant_amount_3']);
							unset($getData[$key]['offer_tenure_1']);
							unset($getData[$key]['offer_tenure_2']);
							unset($getData[$key]['offer_tenure_3']);
							unset($getData[$key]['offer_roi_1']);
							unset($getData[$key]['offer_roi_2']);
							unset($getData[$key]['offer_roi_3']);
							unset($getData[$key]['offer_pf_1']);
							unset($getData[$key]['offer_pf_2']);
							unset($getData[$key]['offer_pf_3']);
							
						}
						else
						{						
							$getData[$key]['offers'][2]['total_ranking_offer'];
							unset($getData[$key]['offers'][0]);
							unset($getData[$key]['offers'][1]);
							unset($getData[$key]['offer_id']);	
							unset($getData[$key]['offer_month_1']);
							unset($getData[$key]['offer_month_2']);	
							unset($getData[$key]['offer_month_3']);	
							unset($getData[$key]['offer_grant_amount_1']);
							unset($getData[$key]['offer_grant_amount_2']);
							unset($getData[$key]['offer_grant_amount_3']);
							unset($getData[$key]['offer_tenure_1']);
							unset($getData[$key]['offer_tenure_2']);
							unset($getData[$key]['offer_tenure_3']);
							unset($getData[$key]['offer_roi_1']);
							unset($getData[$key]['offer_roi_2']);
							unset($getData[$key]['offer_roi_3']);
							unset($getData[$key]['offer_pf_1']);
							unset($getData[$key]['offer_pf_2']);
							unset($getData[$key]['offer_pf_3']);
						}	
					}
					
				}
				if(!empty($new_arr)){
					$statusOnOff = ExternalConnectorsModel::externalConnects('CHECKCASHEOFFERS');
					if($statusOnOff == 1){
						$casheAppModel = new CasheAppModel();
						$casheOfferArray = $casheAppModel->casheOffers($mobilePhoneNumber,$birthDate,$monthlyIncome);
						$cashedata["lender_name"] = "CASHe";
						$cashedata["offers"][] = $casheOfferArray;
						if(!empty($casheOfferArray)){
							array_push($getData,$cashedata);
							array_push($lender_name,"CASHe");
							$lendersMainArray['CASHe'][] = "CASHe";
						}
					}
				}
				
			
				$this->updateKnockoutLender($creditAppUUID, $lender_name); 
			
		return $getData;
    }
	
	function my_array_merge(&$array1, &$newarray) 
	{
		$result = Array();
		foreach($array1 as $key => &$value) 
		{
			$result[$key] = $value;
			$result[$key]['offers'] = $newarray[$key];
		}
		
		return $result;
	}
	
	function updateKnockoutLender($creditAppUUID, $lender_name)
	{
		$new_arr_str = implode(', ', $lender_name);
		$affected = DB::table('credit_app')
              ->where('creditapp_uuid', $creditAppUUID)
              ->update(['knockout_lenders' => $new_arr_str]);
	}
	
	function checkIfUserConsumedOffer($creditAppUUID, $arrLender, $lender_name,$lendersMainArray)
	{
		$arr = $arrLender;
		if(in_array('Upwards',$lender_name))
		{
			$upwardData = DB::table('upwards_app')
				->select('*')
				->where('creditapp_uid',$creditAppUUID)
				->get();
			$upwardData = $upwardData->count();
			if(!empty($upwardData))
			{	
				foreach ($lendersMainArray['Upwards'] as $key1 => $value) {
					if (($key = array_search($value, $arr)) !== false) {
						unset($arr[$key]);
					}
				}
			}

		}
		
		if(in_array('MoneyView',$lender_name))
		{
			$moneyviewData =DB::table('moneyview_app')
			->select('*')
			->where('creditapp_uid',$creditAppUUID)
			->get();
			$moneyviewData = $moneyviewData->count();
			if(!empty($moneyviewData))
			{
				foreach ($lendersMainArray['MoneyView'] as $key1 => $value) {
					if (($key = array_search($value, $arr)) !== false) {
						unset($arr[$key]);
					}
				}
			}

		}
	
		if(in_array('CASHe',$lender_name))
		{
			$casheData =DB::table('cashe_app')
			->select('*')
			->where('creditapp_uid',$creditAppUUID)
			->get();
			$casheData = $casheData->count();
			if(!empty($casheData))
			{	
				foreach ($lendersMainArray['CASHe'] as $key1 => $value) {
					if (($key = array_search($value, $arr)) !== false) {
						unset($arr[$key]);
					}
				}
			}
		}
	
		return $arr;
		

	}
	 
}
