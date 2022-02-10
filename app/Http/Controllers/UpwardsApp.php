<?php

namespace App\Http\Controllers;

use App\Models\UpwardsAppModel;
use Illuminate\Http\Request;

class UpwardsApp extends Controller
{

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeUpwards(Request $request,$app_id){
        $upwardsApp = new UpwardsAppModel();
        $response = $upwardsApp->saveUpwardsDetails($request,$app_id);
        return $response;
    }

       //upward testing
   //  public function checkUpwardStatus(Request $request){
   //      $upwardsAppModel = new UpwardsAppModel();
   //      $response = $upwardsAppModel->getUpwardStatus($request);
   //      return $response;
   //   }

     public function upwardAccessToken(){
        $upwardsAppModel = new UpwardsAppModel();
        $response = $upwardsAppModel->getUpwardAccessToken();
        return $response;
     }

     public function initiateLoan(Request $request){
        $upwardsApp = new UpwardsAppModel();
        $response = $upwardsApp->initiateLoanApplication($request);
        return $response;
     }

     public function showOffers(Request $request,$id){
         $upwardsoffers = new UpwardsAppModel();
         $response = $upwardsoffers->getUpwardsOthersOffer($request,$id);
         return $response;
     }
}
