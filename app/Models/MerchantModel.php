<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\channelModel;
use App\Models\ErrorLogModel;
use DB;
use Exception;

class MerchantModel extends Model
{
    use HasFactory;

    protected $table = "merchant";
    
    protected $primaryKey = "merchant_uid";

    public $incrementing = false;
    
    public $timestamps = true; 

    public function generateMerchantData($num)
	{
		try
		{
			ini_set('max_execution_time', 0);
			$channel = DB::select('select channel_uid from channel'); 
			$channel_uid = $channel[0]->channel_uid;
			$uID_array = [];
			for($x =0; $x < $num; $x++)
			{
				$uID_array[$x]['merchant_uid'] = $this->generateUUID();
				$uID_array[$x]['url_segment'] = $this->generateRandomUrlSegment();
				$uID_array[$x]['channel'] = $channel_uid;
				$uID_array[$x]['enabled'] = 1;
				$uID_array[$x]['name'] = NULL;
				$uID_array[$x]['website'] = NULL;
				$uID_array[$x]['logo'] = NULL;
				$uID_array[$x]['optimistic_lock_field'] = NULL;
				$uID_array[$x]['parent_uid'] = NULL;
				$uID_array[$x]['servicing_system_id'] = NULL;
				$uID_array[$x]['address_line1'] = NULL;
				$uID_array[$x]['address_line2'] = NULL;
				$uID_array[$x]['postal_code'] = NULL;
				$uID_array[$x]['city'] = NULL;
				$uID_array[$x]['state'] = NULL;
				$uID_array[$x]['country'] = NULL;
				$uID_array[$x]['updated_bymerchant_id'] = NULL;
				$uID_array[$x]['pan'] = NULL;
				$uID_array[$x]['gstn'] = NULL;
				$uID_array[$x]['email'] = NULL;
				$uID_array[$x]['helpline_number'] = NULL;
				$uID_array[$x]['twitter'] = NULL;
				$uID_array[$x]['facebook'] = NULL;
				$uID_array[$x]['category'] = NULL;
				$uID_array[$x]['status'] = NULL;
				$uID_array[$x]['is_deleted'] = NULL;
				$uID_array[$x]['servicing_system'] = NULL;
			}
			foreach($uID_array as $value)
			{
			 $data = MerchantModel::insert($value);
			}
			
			if($data){
				return response([
					'success' => 'true',
					'message' => 'QR code has been generated successfully for '. $num,
				],200);            
			}
            else
            {
				return response([
					'success' => 'false',
					'message' => 'something went wrong!'
				],400);
			}
		}
        catch (Exception $e)
        {
            $code = $e->getCode();
            $message = $e->getMessage();
            ErrorLogModel::LogError($status = 500, $code, $message);
            $errolog = new ErrorLogModel();
            return $errolog->genericMsg(); 
        }
	}
	public function generateRandomUrlSegment()
	{
		$random_numbers = "";
	    $str_result ='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$random_numbers = substr(str_shuffle($str_result),0,8);		
		return $random_numbers;
	}
	
	public function generateUUID()
	{
		$uUID = "";
		$uUID =(string) Str::uuid();
		return $uUID;
	}
}