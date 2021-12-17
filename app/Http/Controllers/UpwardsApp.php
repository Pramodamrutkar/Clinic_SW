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
}
