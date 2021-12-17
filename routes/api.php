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
    Route::get('/search-list/{lang}', [SmartListController::class, 'searchList']);
    Route::get('/formula/{postalCode}/{fKey_1}/{fval_1}/{fKey_2}/{fval_2}/{fKey_3}/{fval_3}/{fKey_4}/{fval_4}/{fKey_5}/{fval_5}', [FormulaBuilderEngineController::class, 'searchOffer']);
//});






// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
