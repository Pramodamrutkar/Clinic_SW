<?php

use App\Http\Controllers\CasheApp;
use App\Http\Controllers\CreditProspectController;
use App\Http\Controllers\MoneyViewApp;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\SmartListController;
use App\Http\Controllers\UpwardsApp;
use App\Http\Controllers\FormulaBuilderEngineController;
use App\Http\Controllers\PersonalAccessTokenController;
use App\Http\Controllers\InternalReportExportController;
use App\Http\Controllers\Merchant;
use App\Http\Controllers\SendReminderTostartBgServiceController;
use App\Http\Controllers\MoneyTapController;
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

Route::post('/lap/authenticate',[PersonalAccessTokenController::class,'lapAuthenticate']);

//update data from sf & show data to sf.
Route::put('/process/form/{id}',[CreditProspectController::class,'patchSftoLap']);
Route::get('/process/offers/{id}',[UpwardsApp::class,'showOffers']);

//Route::post('/application/{app_id}', [CreditProspectController::class,'storeDatatoSF']);
//Route::post('/upward-status', [UpwardsApp::class,'checkUpwardStatus']);
//Route::post('/upward-token', [UpwardsApp::class,'upwardAccessToken']);
//Route::post('/cache-offers', [CasheApp::class,'getCasheOffers']);
//Route::post('/create-cache-user/{app_id}', [CasheApp::class,'createUserWithCache']);
//Route::get('/mv-token1/{id}', [MoneyViewApp::class,'getMToken']);

Route::get('/generate-merchant-qr/{num}', [Merchant::class, 'saveMerchantData']);
Route::get('/data-sp', [InternalReportExportController::class,'export']);

Route::get('/comm-details', [SendReminderTostartBgServiceController::class,'commDetails']);
Route::get('/{sf}/offers-details/{uuID}', [FormulaBuilderEngineController::class, 'searchOffer']);

Route::group(['middleware' => ['jwt.verify']], function () {
    Route::post('logout', [OtpController::class, 'logout']);
    Route::post('save-creditapp', [CreditProspectController::class, 'storePersonalInfoInCreditApp']);
    Route::post('/save-smartlist', [SmartListController::class, 'store']);
    Route::get('/smartlist', [SmartListController::class, 'index']);
    Route::post('/moneyview/{app_id}', [MoneyViewApp::class, 'storeMoneyView']);
    Route::post('/upwards/{app_id}', [UpwardsApp::class, 'storeUpwards']);
    //using prospect id user/{app_id}
    Route::get('/user/{app_id}',[CreditProspectController::class, 'userDetails']);
    //using creditapp_uid id user/{app_id}
    Route::post('/return-user/{app_id}',[CreditProspectController::class, 'returnUserProfile']);
    Route::get('/offer-screen/{app_id}',[MoneyViewApp::class, 'showOfferChart']);
    Route::get('/formula/{uuID}', [FormulaBuilderEngineController::class, 'searchOffer']);
    Route::post('/initiate-loan', [UpwardsApp::class, 'initiateLoan']);
    Route::get('/cashe-download/{app_id}', [CasheApp::class,'casheDownloadUrl']);
    Route::post('/moneytap/{app_id}', [MoneyTapController::class,'storeMoneyTapDetails']);

});

//Route::get('/checkMtToken', [MoneyTapController::class,'checkTokenMT']);
