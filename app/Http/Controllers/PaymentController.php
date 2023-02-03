<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\WebhookResponses;
use App\Models\WithdrawRequest;
use Illuminate\Support\Facades\Http;
use Monolog\Processor\WebProcessor;
use Illuminate\Support\Arr;

class PaymentController extends Controller
{
    public function EntryInDeposit(Request $request) {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required',
            'amount' => 'required|numeric',
            'transaction_id' => 'required'
        ]);
        $errorCombined = array();
        foreach( $validator->errors()->all() as $error) {
            array_push($errorCombined, $error);
        }
        if ($validator->fails()) {
            $response = [
                'status' => false,
                'message' => "Invalid fields sent",
                'errors' => $errorCombined,
            ];
            return response()->json($response, 400);
        }
        
        $fields = $validator->validated();
        $record = Deposit::create([
            'payment_method' => $fields['payment_method'],
            'amount' => $fields['amount'],
            'transaction_id' => $fields['transaction_id'],
            'user_id' => $request->user()->id
        ]);

        if(is_null($record)) {
            $response = [
                'status' => false,
                'message' => 'there is a problem creating deposit record'
            ];
            return response()->json($response, 500);
        }

        $response = [
            'status' => true,
            'message' => "Deposit entry is successful",
        ];
        return response()->json($response, 201);
    }

    public function EntryWithDraw(Request $request) {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required',
            'amount' => 'required|numeric',
            'payment_id' => 'required'
        ]);
        $errorCombined = array();
        foreach( $validator->errors()->all() as $error) {
            array_push($errorCombined, $error);
        }
        if ($validator->fails()) {
            $response = [
                'status' => false,
                'message' => "Invalid fields sent",
                'errors' => $errorCombined,
            ];
            return response()->json($response, 400);
        }
        
        $fields = $validator->validated();
        $record = WithdrawRequest::create([
            'payment_method' => $fields['payment_method'],
            'amount' => $fields['amount'],
            'payment_id' => $fields['payment_id'],
            'user_id' => $request->user()->id
        ]);

        if(is_null($record)) {
            $response = [
                'status' => false,
                'message' => 'there is a problem creating withdraw request'
            ];
            return response()->json($response, 500);
        }

        $response = [
            'status' => true,
            'message' => "Withdraw entry is successful",
        ];
        return response()->json($response, 201);
    }

    public function NowPaymentsProcess(Request $request) {
        date_default_timezone_set('Asia/Kolkata');
        $testing = false;
        $fiat_currency = "inr"; //usd, inr etc..

        if($testing) {
            $fiat_currency = "usd";
            $api_key = "D2Y58S9-BWZ456A-Q8B31GX-YDWYW0K"; //sandbox environment
            $base_url = "https://api-sandbox.nowpayments.io";
        }
        else {
            $api_key = "9HZ1CET-NEP4EZR-KRHX7AX-R71ZXEF"; //production environment
            $base_url = 'https://api.nowpayments.io';
        }

        $ipn_callback_url = config('app.custom_config.admin_url') . "/api/webhook-nowpayments";  

        //Get the status of the api
        if($request['resource'] == "get_status") {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                ]) -> Get($base_url . '/v1/status');
            if(is_null($response)) {
                $response = [
                    'status' => false,
                    'message' => "Apis are not available"
                ];
                return response()->json($response, 400);
            }
            else if($response['message'] != "OK") {
                $response = [
                    'status' => false,
                    'message' => "Apis are not available"
                ];
                return response()->json($response, 400);
            }

            $response = [
                'status' => true,
                'message' => "Apis are available"
            ];
            return response()->json($response, 200);
        }
        //Get available currencies
        if($request['resource'] == "get_crypto_list") {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'x-api-key' => $api_key,
            ])->Get($base_url . '/v1/currencies')['currencies'];
            
            if(is_null($response)) {
                $response = [
                    'status' => false,
                    'available_currencies' => []
                ];
                return response()->json($response, 400);
            }
            $response = [
                'status' => true,
                'available_currencies' => $response
            ];
            return response()->json($response, 200);
        }
        //print_r($available_currencies);

        //Get the minimum amount for the pair of crypto currency
        if($request['resource'] == "get_payment_address") {
            $currency_from = $request['currency_from']; // This is the currency buyer want to pay..
            $currency_to = $request['currency_to']; //This is the currency merchant want
            $amount = $request['amount']; // amount is in currency_from and this is the cost of the product
            $order_id = $request['order_id'];
            $order_description = $request['user_id'];
            
            //echo($currency_from . "|". $currency_to . "|". $amount . "|". $order_id . "|" . $order_description);
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'x-api-key' => $api_key,
            ]) -> Get($base_url . '/v1/min-amount', [
                'currency_from' => $currency_from,
                'currency_to' => $currency_to
            ]);

            if($response->status() != 200) {
                $response = [
                    'status' => false,
                    'message' => "Unable to get Minimum amount"
                ];
                return response()->json($response, 400);
            }
            
            //echo $res->getBody();
            $result['min_amount'] = $response['min_amount'];
            
            //Estimated amount that user need to pay in his choice of currency(currency to)
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'x-api-key' => $api_key,
                ]) -> get($base_url . '/v1/estimate', [
                    'amount' => $amount,
                    'currency_from' => $fiat_currency,
                    'currency_to' => $currency_from
                ]);
                
            if($response->ok() != true) {
                $response = [
                    'status' => false,
                    'message' => "Unable to get Estimated Amount"
                ];
                return response()->json($response, 400);
            }
                
            //echo $res->getBody();
            $result['estimated_amount'] = $response['estimated_amount'];
            
            // if($estimated_amount > $min_amount) {
            //   echo("You are good to go");
            // }
            // else {
            //   echo("Increase the amount please");
            //   exit;
            // }
            if($result['estimated_amount'] <= $result['min_amount']) {
                return response()->json(['completed' => false], 200);
            }
        
            //Get the address to pay
            $body = [
                'price_amount' => $amount,
                'price_currency' => $fiat_currency,
                'pay_currency' => $currency_from,
                'ipn_callback_url' => $ipn_callback_url,
                'order_id' => $order_id . "-". time(),
                'order_description'=> $order_description
            ];
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'x-api-key' => $api_key,
                'Content-Type' => 'application/json'
                ]) -> post($base_url . '/v1/payment', $body);

            if($response->status() != 201) {
                $response = [
                    'status' => false,
                    'message' => "Unable to get Address to pay"
                ];
                return response()->json($response, 400);
            }
            
            $result['completed'] = true;
            $result['payment_id'] = $response['payment_id'];
            $result['pay_address'] = $response['pay_address'];
            
            return response()->json($result, 200);
        }

        if($request['resource'] == "get_payment_status") {
            $payment_id = $request['payment_id'];

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'x-api-key' => $api_key,
                ]) -> get($base_url . '/v1/payment/' . $payment_id);

            if($response->status() != 200) {
                $response = [
                    'status' => false,
                    'message' => "Could not get the payment status"
                ];
                return response()->json($response, 400);
            }

            $result['payment_status'] = $response['payment_status'];
            $result['price_amount'] = $response['price_amount'];
            $result['pay_amount'] = $response['pay_amount'];
            $result['actually_paid'] = $response['actually_paid'];
            
            return response()->json($result, 200);
        }
    }

    public function NowPaymentsWebhook(Request $request) {
        $error_msg = "Unknown error";
        $auth_ok = false;
        $request_json = "";
        $request_data = null;
        $ipn_secret_key = "PYhYXeofPhKaEkiVCpQ3yO+d4Kc6LynW";  //Live 
        // $ipn_secret_key = "8XT9sOeg0sS3ST6xI8J4M1I5PXmmudZD"; //Sandbox
        
        if (isset($_SERVER['HTTP_X_NOWPAYMENTS_SIG']) && !empty($_SERVER['HTTP_X_NOWPAYMENTS_SIG'])) {
            $recived_hmac = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'];
            $request_json = file_get_contents('php://input');
            WebhookResponses::create(['response' => $request_json ]);
            $request_data = json_decode($request_json, true);
            ksort($request_data);
            $sorted_request_json = json_encode($request_data, JSON_UNESCAPED_SLASHES);
            if ($request_json !== false && !empty($request_json)) {
                $hmac = hash_hmac("sha512", $sorted_request_json, trim($ipn_secret_key));
                if ($hmac == $recived_hmac) {
                    $auth_ok = true;
                } else {
                    $error_msg = 'HMAC signature does not match';
                }
            } else {
                $error_msg = 'Error reading POST data';
            }
        } 
        else {
            $error_msg = 'No HMAC signature sent.';
        }

        //Actual response recevied
        // {
        //     "payment_id":4331618926,
        //     "invoice_id":null,
        //     "payment_status":"finished",
        //     "pay_address":"0xa020f0Ec793d3E2A98e85a1C3Cb872A94556B31d",
        //     "price_amount":100,
        //     "price_currency":"inr",
        //     "pay_amount":0.00448866,
        //     "actually_paid":0,
        //     "actually_paid_at_fiat":0,
        //     "pay_currency":"bnbbsc",
        //     "order_id":"lsdjfljdsljfsdf",
        //     "order_description":"Buy some crypto to wallet recharge",
        //     "purchase_id":"5241307872",
        //     "created_at":"2022-11-14T17:25:38.305Z",
        //     "updated_at":"2022-11-14T17:25:40.309Z",
        //     "outcome_amount":0.003777,
        //     "outcome_currency":"bnbbsc"
        // }
        
        $temp = explode("-", $request_data['order_id']);
        $coins = $temp[0];
        if($auth_ok) {
            if($request_data['payment_status'] == "finished" || $request_data['payment_status'] == "confirming") {
                $user_id = $request_data['order_description'];
                $user = User::where('id', $user_id)->first();
                $user->gamedata->Coins = (string)((int)$user->gamedata->Coins + (int)$request_data['price_amount']);
                $user->gamedata->save();
            }
        }
    }

    public function UPIGatewayCreateOrder(Request $request) {
        $key = "67e4bba7-8e5d-4086-beaf-a9a2577bf670";  // Put Your key here

        $client_txn_id = $request['transaction_id'];
        $body = [
            'key' => $key,
            'p_info' => "Ludo Master Topup of " . $request['amount'],
            'amount' => $request['amount'],
            'customer_name' => $request['name'],
            'customer_email' => $request['email'],
            'customer_mobile' => $request['phone'],
            'redirect_url' => "https://sample.html",
            'client_txn_id' => $client_txn_id
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://merchant.upigateway.com/api/create_order', $body);
        
        if($response->status() != 200 || $response->status() != 201) {
            $response = [
                'status'=>false,
                'message' => $response['msg']
            ];
            return response()->json($response, 400);
        }

        if($response['status']) {
            $finalPayload = $response['data'];
            $finalPayload['client_txn_id'] = $client_txn_id;
            $finalPayload['status'] = true;
            return response()->json($finalPayload, 200);
        }
        else {
            $response = [
                'status'=>false,
                'message' => "Failed to create order"
            ];
            return response()->json($response, 400);
        }
    }

    public function UPIGatewayOrderStatus(Request $request) {
        date_default_timezone_set('Asia/Kolkata');

        $json_body = file_get_contents('php://input');
        $payload = json_decode($json_body, true);

        $body = [
            'key' => "67e4bba7-8e5d-4086-beaf-a9a2577bf670",
            'client_txn_id' => $payload['client_txn_id'],
            'txn_date' => date("d-m-Y")
        ];
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://merchant.upigateway.com/api/check_order_status', $body);

        if($response->status() != 200 || $response->status() != 201) {
            $response = [
                'status'=>false,
                'message' => "Failed to check the order status"
            ];
            return response()->json($response, 400);
        }

        if($response['status']) {
            if($response['data']['status'] == "success") {
                $finalPayload['upi_txn_id'] = $response['data']['upi_txn_id'];
                $finalPayload['customer_vpa'] = $response['data']['customer_vpa'];
                $finalPayload['status'] = true;
                return response()->json($finalPayload, 200);
            }
            else {
                $finalPayload['status'] = false;
                return response()->json($finalPayload, 400);
            }
        }
        else {
            $finalPayload['status'] = false;
            return response()->json($finalPayload, 400);
        }
    }
}
