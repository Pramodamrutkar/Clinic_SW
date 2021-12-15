<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\Otp as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Otp extends Authenticatable implements JWTSubject

{
    use HasFactory, Notifiable;
    
    protected $table = "otp";
    protected $primaryKey = "id";
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code', 'device_locator',
    ];
    
    public function getJWTIdentifier()
    {
        return $this->getKey();

    }//end getJWTIdentifier()


    public function getJWTCustomClaims()
    {
        return [];

    }//end getJWTCustomClaims()

}
