<?php

namespace App\Http\Controllers;

use App\Models\CreditApp;
use App\Models\CreditProspect;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\Response;



class CreditProspectController extends Controller
{

    /**
     * Store basic credit prospect details on email or mobile no entered
     */
    public function storeBasicDetails(Request $request){
    
        $request->validate([
            'issend_toemail' => 'required|boolean',
            //'email' => 'required|string|unique:credit_prospect,email',
            'channel_id' => 'required|string',
        ]);
        
        //$request->credituid = (string) Str::uuid();
        //return CreditProspect::create($request->all());

        $obj = new CreditProspect();
        $response = $obj->saveBasicsdetails($request);
        return $response; 
    }  

    public function storePersonalInfoInCreditApp(Request $request){
       $creditAppobj = new CreditApp();
       $response = $creditAppobj->savePersonalInformationiInApp($request);
       return $response;
    }
}
