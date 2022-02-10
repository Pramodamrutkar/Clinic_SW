<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CommunicationBgServiceModel;

class SendReminderTostartBgServiceController extends Controller
{
    public function commDetails(Request $request){
        $commModel = new CommunicationBgServiceModel();
        $response = $commModel->getCommDetails($request);
        return $response;
    }
}
