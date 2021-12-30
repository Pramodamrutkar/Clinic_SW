<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends Model
{
    use HasFactory;

    protected $table = "personal_access_tokens";
    
    protected $primaryKey = "id";
    
    public $timestamps = true; 

    public function generateToken($tokenId){

        $token = openssl_random_pseudo_bytes(25);
        $token = bin2hex($token);
        $this->token_id = $tokenId;
        $this->token = $token;
        $this->expire_at  = date("Y-m-d H:i:s", strtotime(date('Y-m-d H:i:s') . "45 seconds"));
        if($this->save()){
            return $this->token;
        }
    }

    public static function checkTokenExpire($token,$tokenId){
        $expiryTime = date("Y-m-d H:i:s");
        $tokenCount = PersonalAccessToken::where("token",trim($token))->where("token_id",trim($tokenId))->where('expire_at', '>=', $expiryTime)->count();
        return $tokenCount;
    }

}
