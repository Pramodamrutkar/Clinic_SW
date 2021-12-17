<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\support\Facades\DB;
use App\Models\FormulaBuilderEngine;
use App\Models\Locations;
use App\Models\Offers;
use App\Models\OfferWeightage


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
		$postalCode = trim($request->postalCode);
		$amountKey = trim($request->fKey_1);
		$amountValue = trim($request->fval_1);
		$ageKey = trim($request->fKey_2);
		$ageValue = trim($request->fval_2);
		$City_TierKey = trim($request->fKey_3);
		$City_TierValue = trim($request->fval_3);
		$genderKey = trim($request->fKey_4);
		$genderValue = trim($request->fval_4);
		$employeement_typeKey = trim($request->fKey_5);
		$employeement_typeValue = trim($request->fval_5);
	//
	
	
        $locationData = Locations::where('postal_code', $postalCode )->first();
		//dd($locationData);
		
		if($amountValue <= 20000)
		{
			return "Not eligible for any offer";
		}
		$offerName = DB::select('SELECT offer_name FROM `formula_builder_engine` 
		where (offer_key ="'.$amountKey.'" AND offer_min_number <= "'.$amountValue.'" AND offer_max_number >= "'.$amountValue.'" AND status = 1)
		UNION ALL
		SELECT offer_name FROM `formula_builder_engine`
		where (offer_key ="'.$amountKey.'" AND offer_min_number <= "'.$amountValue.'" AND offer_max_number >= "'.$amountValue.'" AND status = 1)
		AND (offer_key ="'.$ageKey.'" AND offer_min_number <= "'.$ageValue.'" AND offer_max_number >= "'.$ageValue.'" AND status = 1) 
		AND (offer_key ="'.$City_TierKey.'" AND offer_min_number = "'.$City_TierValue.'" AND status = 1)
		AND (offer_key ="'.$employeement_typeKey.'" AND offer_min_number = "'.$employeement_typeValue.'" AND status = 1)');
								
		foreach($offerName as $value)
		{
			$new_arr[] = $value->offer_name;
		}								
								
		//DB::connection()->enableQueryLog();
		//dd(DB::getQueryLog());	
		
		$offersData =DB::table('offers')
					->select('*')
					->whereIn('offer_name',$new_arr)
					->get();
					
		$data = array();
		$array1 = json_decode(json_encode($offersData),True);
								//dd($array1);
		foreach($array1 as $key => $val)
		{
									
			$grantAmount_1 = $val['offer_grant_amount_1'] * $amountValue;
			$grantAmount_2 = $val['offer_grant_amount_2'] * $amountValue;
			$grantAmount_3 = $val['offer_grant_amount_3'] * $amountValue;
									
									
			$data[]['offer_amount_1'] = $grantAmount_1;
			$data[]['offer_amount_2'] = $grantAmount_2;
			$data[]['offer_amount_3'] = $grantAmount_3;
									
			//offer 1
			$data[]['calculated_amount_offer_1'] = $data[0]['offer_amount_1']*25/100;
			$data[]['roi_offer_1'] = $val['offer_grant_amount_1'] *50/100;
			$data[]['tenure_offer_1'] = $val['offer_tenure_1'] *25/100;
									
									
			//offer2
			$data[]['calculated_amount_offer_2'] = $data[1]['offer_amount_2']*25/100;
			$data[]['roi_offer_2'] = $val['offer_grant_amount_2'] *50/100;
			$data[]['tenure_offer_2'] = $val['offer_tenure_2'] *25/100;
									
			//offer3
			$data[]['calculated_amount_offer_3'] = $data[2]['offer_amount_3']*25/100;
			$data[]['roi_offer_3'] = $val['offer_grant_amount_3'] *50/100;
			$data[]['tenure_offer_3'] = $val['offer_tenure_3'] *25/100;
									
			//Total ranking
			$data[]['total_ranking_offer_1'] = $data[3]['calculated_amount_offer_1'] + $data[4]['roi_offer_1'] + $data[5]['tenure_offer_1'];			
			$data[]['total_ranking_offer_2'] = $data[6]['calculated_amount_offer_2'] + $data[7]['roi_offer_2'] + $data[8]['tenure_offer_2'];				
			$data[]['total_ranking_offer_3'] = $data[9]['calculated_amount_offer_3'] + $data[10]['roi_offer_3'] + $data[11]['tenure_offer_3'];
//dd($data);
		}
			//dd($data);
			$newarray = array_chunk($data,15);
			$getData = $this->my_array_merge($array1, $newarray);
			dd($getData);
								
															
        return $offersData;
    }
	
	function my_array_merge(&$array1, &$newarray) 
	{
		$result = Array();
		foreach($array1 as $key => &$value) 
		{
			$result[$key] = array_merge($value, $newarray[$key]);
		}
		
		return $result;
	}
}
