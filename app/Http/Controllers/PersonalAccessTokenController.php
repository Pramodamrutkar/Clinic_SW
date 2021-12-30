<?php

namespace App\Http\Controllers;

use App\Models\PersonalAccessToken;
use App\Models\TokenTypes;
use Illuminate\Http\Request;

class PersonalAccessTokenController extends Controller
{
    
    public function lapAuthenticate(Request $request){
        $tokenTypes = new TokenTypes();
        $response = $tokenTypes->validatePartnerSecretkey($request);
        return $response;
    }
}
