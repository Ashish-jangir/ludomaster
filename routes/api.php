<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FriendsController;
use App\Http\Controllers\GamedataController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\GeneralRequestController;

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

Route::get('/', function(Request $request) {
    return "Welcome";
});

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/resend-email', [AuthController::class, 'resendEmail']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-with-facebook', [AuthController::class, 'loginWithFacebook']);
Route::post('/login-with-customid', [AuthController::class, 'loginWithCustomID']);

Route::post('/forgot-password', [AuthController::class, 'forgot']);

Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/reset', [AuthController::class, 'reset']);
    Route::post('/link-facebook-account', [AuthController::class, 'linkFacebookAccount']);
});

// Api Calls For Friendship routes From Here
Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::post('/add-friend', [FriendsController::class, 'addFriend']);
    Route::post('/accept-friend', [FriendsController::class, 'acceptFriend']);
    Route::post('/deny-friend', [FriendsController::class, 'denyFriend']);
    Route::post('/remove-friend', [FriendsController::class, 'removeFriend']);
    Route::get('/get-friends', [FriendsController::class, 'getFriends']);
    
});

// Api Calls For data routes From Here
Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::get('/get-game-history', [GamedataController::class, '']);     // TODO
    Route::get('/my-withdraw-data', [GamedataController::class, '']);     // TODO
    Route::post('/get-user-data', [GamedataController::class, 'getUserData']);
    Route::post('/update-user-data', [GamedataController::class, 'updateUserData']);
    Route::get('/get-photon-token', [GamedataController::class, 'getPhotonToken']);
    Route::post('/deposit-entry', [PaymentController::class, 'EntryInDeposit']);
    Route::post('/withdraw-entry', [PaymentController::class, 'EntryWithDraw']);
    Route::post('/send-message', [GeneralRequestController::class, 'sendMessage']);
    Route::get('/get-messages', [GeneralRequestController::class, 'getMessages']);
    Route::post('/nowpayments', [PaymentController::class, 'NowPaymentsProcess']);
    Route::post('/upigateway-create_order', [PaymentController::class, 'UPIGatewayCreateOrder']);
    Route::post('/upigateway-order_status', [PaymentController::class, 'UPIGatewayOrderStatus']);
    Route::get('/razorpay-orderid', [PaymentController::class, 'RazorPayOrderId']);
});
Route::get('/auth-for-photon', [GamedataController::class, 'authForPhoton']);
Route::get('/webhook-nowpayments', [PaymentController::class, 'NowPaymentsWebhook']);

Route::get('/app-info', function() {
    $response = [
        'url' => config('app.custom_config.app_url'),
        'version' =>config('app.custom_config.app_version'),
        'noticeboard' => config('app.custom_config.notice_board')
    ];
    return response()->json($response,200);
});