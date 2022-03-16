<?php

namespace App\Http\Controllers;

use App\Models\NiraModel;
use Illuminate\Http\Request;

class NiraController extends Controller
{

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeNiraDetails(Request $request, $app_id){
       $niraModel = new NiraModel();
       $response = $niraModel->storeNiraData($request,$app_id);
       return $response;
    }
}
