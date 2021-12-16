<?php

namespace App\Providers;

use App\Models\Otp;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class OtpUserProvider implements UserProvider
{
    public function retrieveByToken ($identifier, $token) {
        throw new Exception('Method not implemented.');
    }

    public function updateRememberToken (Authenticatable $user, $token) {
        throw new Exception('Method not implemented.');
    }

    public function retrieveById ($identifier) {
        return Otp::find($identifier);
    }

    public function retrieveByCredentials (array $credentials) {
        $phoneOrEmail = $credentials['device_locator'];
  
        return Otp::where('device_locator', $phoneOrEmail)->where('used',0)->first();
    }

    public function validateCredentials (Authenticatable $user, array $credentials) {
        $otp = $credentials['code'];
        $deviceLocator = $credentials['device_locator'];
        $otpData = Otp::where('code', $otp)->where('device_locator',$deviceLocator)->first();
        return $otp == $otpData['code'];
    }
}