<?php

namespace App\Http\Controllers;

use App\Models\CasheAppModel;
use Illuminate\Http\Request;

class CasheApp extends Controller
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * 
     */
    public function getCasheOffers(Request $request){
        $casheAppModel = new CasheAppModel();
        $response = $casheAppModel->cacheOffers($request);
        return $response;
    }
}
