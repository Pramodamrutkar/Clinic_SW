<?php

namespace App\Http\Controllers;

use App\Models\CasheAppModel;
use Illuminate\Http\Request;

class CasheApp extends Controller
{
    // /**
    //  * @param  \Illuminate\Http\Request  $request
    //  * 
    //  */
    // public function getCasheOffers(Request $request){
    //    // $casheAppModel = new CasheAppModel();
    //    // $response = $casheAppModel->casheOffers($request);
    //    // return $response;
    // }

    public function createUserWithCache($app_id){
        $casheAppModel = new CasheAppModel();
        $response = $casheAppModel->createUserWithCache($app_id);
        return $response;
    }

    public function casheStatus($lender_system_id){
        $casheAppModel = new CasheAppModel();
        $response = $casheAppModel->getCacheStatus($lender_system_id);
        return $response;
    }
    
    public function casheDownloadUrl($app_id){
        $casheAppModel = new CasheAppModel();
        $response = $casheAppModel->sendCasheDownloadLink($app_id);
        return $response;
    }
}
