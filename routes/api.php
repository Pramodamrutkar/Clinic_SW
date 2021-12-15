<?php

use App\Http\Controllers\CreditProspectController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\SmartListController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/apply-loan',[CreditProspectController::class,'storeBasicDetails']);
Route::post('/verifyotp',[OtpController::class,'authenticate']);    
Route::post('/send-otp',[OtpController::class,'sendOtp']);

//Route::group(['middleware' => ['jwt.verify']], function() {
    Route::post('save-creditapp',[CreditProspectController::class,'storePersonalInfoInCreditApp']);
    Route::post('/save-smartlist', [SmartListController::class, 'store']);
    Route::get('/smartlist', [SmartListController::class, 'index']);
//});





// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
