<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
#use Illuminate\support\Facades\DB;
use App\Models\FormulaBuilderEngine;
use App\Models\Locations;
use App\Models\Offers;
use App\Models\OfferWeightage;
use App\Models\CreditApp;
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
		
		$from = new DateTime($birthDate);
		$to   = new DateTime('today');
		$age  = $from->diff($to)->y;
		
	
        $locationData = Locations::where('postal_code', $postalCode )->first();
		
		if($monthlyIncome <= 20000)
		{
			return [];
		}
		/* if($monthlyIncome > 30001)
		{
			$offerName = DB::select('SELECT offer_name FROM `formula_builder_engine` 
			where (offer_key = "amount" AND offer_number >= 30001 AND status = 1)
			UNION ALL
			SELECT offer_name FROM `formula_builder_engine`
			where (offer_key = "amount" AND offer_number >= 30001 AND status = 1)
			AND (offer_key = "age" AND offer_min_number <= "'.$age.'" AND offer_max_number >= "'.$age.'" AND status = 1) 
			AND (offer_key = "City_Tier" AND offer_min_number = "'.$locationData->city_tier.'" AND status = 1)
			AND (offer_key = "employeement_type" AND offer_min_number = "'.$employeementStatus.'" AND status = 1)');
		} */
		
		$offerName = DB::select('SELECT offer_name FROM `formula_builder_engine` 
		where (offer_key = "amount" AND offer_min_number <= "'.$monthlyIncome.'" AND offer_max_number >= "'.$monthlyIncome.'" AND status = 1)
		UNION ALL
		SELECT offer_name FROM `formula_builder_engine`
		where (offer_key = "amount" AND offer_min_number <= "'.$monthlyIncome.'" AND offer_max_number >= "'.$monthlyIncome.'" AND status = 1)
		AND (offer_key = "age" AND offer_min_number <= "'.$age.'" AND offer_max_number >= "'.$age.'" AND status = 1) 
		AND (offer_key = "City_Tier" AND offer_min_number = "'.$locationData->city_tier.'" AND status = 1)
		AND (offer_key = "employeement_type" AND offer_min_number = "'.$employeementStatus.'" AND status = 1)');

	
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
		

		foreach($array1 as $key => $val)
		{ 
			$weightageData = OfferWeightage::where('lender_name', $val['lender_name'])->first();
		
			$grantAmount_1 = $val['offer_grant_amount_1'] * $monthlyIncome;
			$grantAmount_2 = $val['offer_grant_amount_2'] * $monthlyIncome;
			$grantAmount_3 = $val['offer_grant_amount_3'] * $monthlyIncome;
									
			
			$data[] = array('offer_amount_1' =>$grantAmount_1);
			$data[] = array('offer_amount_2' =>$grantAmount_2);
			$data[] = array('offer_amount_3' =>$grantAmount_3);
			
			
			
			//offer 1
			
			$data[]['offer_amount_1'] = $data[0]['offer_amount_1']*$weightageData->amount_weight/100;
			$data[]['offer_amount_1'] = $val['offer_grant_amount_1'] *$weightageData->roi_weight/100;
			$data[]['offer_amount_1'] = $val['offer_tenure_1'] *$weightageData->tenure_weight/100;
									
									
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
			
			$main_array =array();
			foreach($split_array as $key1=> $val)
			{
			
				foreach($val as $key2 => $data_test)
				{									
					$push_data = array('offer_amount' => $data_test[0]);
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

					$a= $getData[$key]['offers'][0]['total_ranking_offer'];
					$b= $getData[$key]['offers'][1]['total_ranking_offer'];
					$c= $getData[$key]['offers'][2]['total_ranking_offer'];
										
					if ($a <= $b && $a <= $c)
					{
						$getData[$key]['offers'][0]['total_ranking_offer'];
						unset($getData[$key]['offers'][1]);
						unset($getData[$key]['offers'][2]);
						
					}					
					else if ($b <= $a && $b <= $c)
					{
						$getData[$key]['offers'][1]['total_ranking_offer'];
						unset($getData[$key]['offers'][0]);
						unset($getData[$key]['offers'][2]);
						
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
	 
}
