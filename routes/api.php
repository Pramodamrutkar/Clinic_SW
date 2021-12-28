<?php

use App\Http\Controllers\CasheApp;
use App\Http\Controllers\CreditProspectController;
use App\Http\Controllers\MoneyViewApp;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\SmartListController;
use App\Http\Controllers\UpwardsApp;
use App\Http\Controllers\FormulaBuilderEngineController;
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

Route::post('/apply-loan', [OtpController::class, 'storeBasicDetails']);
Route::post('/verifyotp', [OtpController::class, 'authenticate']);
Route::post('/send-otp', [OtpController::class, 'sendOtp']);
Route::get('/search-list/{lang}', [SmartListController::class, 'searchList']);
Route::post('/verify-tindob', [CreditProspectController::class, 'verifyViaTin']);

//Route::post('/application/{app_id}', [CreditProspectController::class,'storeDatatoSF']);
//Route::post('/upward-eligibility', [UpwardsApp::class,'checkUpwardEligible']);
//Route::post('/upward-token', [UpwardsApp::class,'upwardAccessToken']);
Route::post('/cache-offers', [CasheApp::class,'getCasheOffers']);

Route::group(['middleware' => ['jwt.verify']], function () {
    Route::post('logout', [OtpController::class, 'logout']);
    Route::post('save-creditapp', [CreditProspectController::class, 'storePersonalInfoInCreditApp']);
    Route::post('/save-smartlist', [SmartListController::class, 'store']);
    Route::get('/smartlist', [SmartListController::class, 'index']);
    Route::post('/moneyview/{app_id}', [MoneyViewApp::class, 'storeMoneyView']);
    Route::post('/upwards/{app_id}', [UpwardsApp::class, 'storeUpwards']);
    //we are using prospect id user/{app_id}
    Route::get('/user/{app_id}',[CreditProspectController::class, 'userDetails']);
    //we are using creditapp_uid id user/{app_id}
    Route::post('/return-user/{app_id}',[CreditProspectController::class, 'returnUserProfile']);
    Route::get('/offer-screen/{app_id}',[MoneyViewApp::class, 'showOfferChart']);
    Route::get('/formula/{uuID}', [FormulaBuilderEngineController::class, 'searchOffer']);    
    Route::post('/initiate-loan', [UpwardsApp::class, 'initiateLoan']);
});
