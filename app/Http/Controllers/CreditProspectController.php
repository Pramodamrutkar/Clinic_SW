<?php

namespace App\Http\Controllers;

use App\Models\CreditApp;
use App\Models\CreditProspect;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\Response;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;



class CreditProspectController extends Controller
{

    /**
     * Store basic credit prospect details on email or mobile no entered
     */
    // public function storeBasicDetails(Request $request){
    
    //     $request->validate([
    //         'issend_toemail' => 'required|boolean',
    //         //'email' => 'required|string|unique:credit_prospect,email',
    //         'channel_id' => 'required|string',
    //     ]);
        
    //     //$request->credituid = (string) Str::uuid();
    //     //return CreditProspect::create($request->all());

    //     $obj = new CreditProspect();
    //     $response =   $obj->saveBasicsdetails($request);
    //     return $response; 
    // }  

    public function storePersonalInfoInCreditApp(Request $request){
        
       $creditAppobj = new CreditApp();
       $response = $creditAppobj->savePersonalInformationiInApp($request);
       return $response;
    }

    public function userDetails($app_id){
       $creditProspect = new CreditProspect();
       $response = $creditProspect->retriveUseronAppID($app_id);
       return $response;
    }

    public function storeDatatoSF($app_id){
      $creditApp = new CreditApp();
      $response = $creditApp->storeDataintoSFDC($app_id);
      return $response;
    }

    public function verifyViaTin(Request $request){
      $creditApp = new CreditApp();
      $response = $creditApp->verifyUsingTin($request);
      return $response;    
    }

    public function returnUserProfile($app_id){
      $creditAppUser = new CreditApp();
      $userResponse = $creditAppUser->profileUser($app_id);
      return $userResponse;
    }

    public function patchSftoLap(Request $request,$id){
      $creditobj = new CreditApp();
      $response = $creditobj->patchPersonalData($request,$id);
      return $response;
    }

}
