<?php

namespace App\Http\Controllers;

use App\Models\MoneyTapModel;
use Illuminate\Http\Request;

class MoneyTapController extends Controller
{
    public function storeMoneyTapDetails(Request $request, $app_id){
       $moneyTapModel = new MoneyTapModel();
       $moneyTapModel->storeMoneyTapData($request,$app_id);
       return $moneyTapModel;
    }
}
