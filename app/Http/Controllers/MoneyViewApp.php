<?php

namespace App\Http\Controllers;

use App\Models\MoneyViewAppModel;
use Illuminate\Http\Request;

class MoneyViewApp extends Controller
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeMoneyView(Request $request,$app_id){
        $moneyViewApp = new MoneyViewAppModel();
        $response = $moneyViewApp->saveMoneyView($request,$app_id);
        return $response;
    }

    public function showOfferChart($app_id){
        $moneyViewApp = new MoneyViewAppModel();
        $response = $moneyViewApp->listOfferChart($app_id);
        return $response;
    }

    public function getMToken($lenderId){
        $moneyViewApp = new MoneyViewAppModel();
        $response = $moneyViewApp->getJourneyUrl($lenderId);
        return $response;
    }
}
