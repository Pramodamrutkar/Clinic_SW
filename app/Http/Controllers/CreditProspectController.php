<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\Response;

use App\Models\CreditProspect;


class CreditProspectController extends Controller
{

    /**
     * Store basic credit prospect details on email or mobile no entered
     */
    public function storeBasicDetails(Request $request){
    
        $request->validate([
            'issend_toemail' => 'required|boolean',
            'channel_id' => 'required|string',
        ]);
        $obj = new CreditProspect();
        $response = $obj->saveBasicsdetails($request);
        return $response; 
        
    }  

}
