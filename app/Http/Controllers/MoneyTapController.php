<?php

namespace App\Http\Controllers;

use App\Models\MoneyTapModel;
use Illuminate\Http\Request;

class MoneyTapController extends Controller
{

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeMoneyTapDetails(Request $request, $app_id){
       $moneyTapModel = new MoneyTapModel();
       $response = $moneyTapModel->storeMoneyTapData($request,$app_id);
       return $response;
    }

    public function checkTokenMT(){
        $moneyTapModel = new MoneyTapModel();
        $response = $moneyTapModel->getMoneyTapToken();
        return $response;
    }


}
