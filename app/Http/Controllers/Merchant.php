<?php

namespace App\Http\Controllers;
use App\Models\MerchantModel;
use Illuminate\Http\Request;

class Merchant extends Controller
{
    public function saveMerchantData($num)
	{
		$MerchantModel = new MerchantModel();
		$qrCode = $MerchantModel->generateMerchantData($num);		
		return $qrCode;			
	}
}