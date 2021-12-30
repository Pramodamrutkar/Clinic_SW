<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TokenTypes extends Model
{
    use HasFactory;
    protected $table = "token_types";
    
    protected $primaryKey = "id";
    
    public $timestamps = true; 

    public function validatePartnerSecretkey(Request $request){
        $partnerName = trim($request->partner_name);
        $secretKey = trim($request->secret_key); 
        $data = TokenTypes::where('partner_name',$partnerName)->where("secret_key",$secretKey)->first();
        $personalAccessToken =new PersonalAccessToken();
        $token = $personalAccessToken->generateToken($data["id"]);
        if(empty($data)){
            return Response([
                'status' => 'false',
                'message' => 'Invalid credentials'
            ],400);            
        }else{
            return Response([
                'status' => 'true',
                'message' => 'valid credentials',
                'token_id' => $data["id"],
                'token' => $token
            ],200);
        }
    }

}
