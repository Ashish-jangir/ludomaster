<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\WithdrawRequest;

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
        ini_set('display_errors', 1);
        error_reporting(~0);
        $testing = true;
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

        $ipn_callback_url = config('app.url') . "/api/webhook-nowpayments";  

        //Get the status of the api
        if($_GET['resource'] == "get_status") {
        try {
            $res = $client->request('GET', $base_url . '/v1/status', [
                'headers' => [
                'accept' => 'application/json',
                ],
            ]);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            echo $responseBodyAsString;
            exit;
        }
        //echo $res->getBody();
        if(json_decode($res->getBody())->message != "OK") {
            echo("Apis are not available");
            exit;
        }

        }

        //Get available currencies
        if($_GET['resource'] == "get_crypto_list") {
        try {
            $res = $client->request('GET', $base_url . '/v1/currencies', [
                'headers' => [
                    'accept' => 'application/json',
                    'x-api-key' => $api_key,
                ],
                ]);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            echo $responseBodyAsString;
            exit;
        }
        $output['available_currencies'] = json_decode($res->getBody())->currencies;
        echo json_encode($output);
        exit;
        //print_r($available_currencies);
        }

        //Get the minimum amount for the pair of crypto currency
        if($_GET['resource'] == "get_payment_address") {
        $json_body = file_get_contents('php://input');
        $payload = json_decode($json_body, true);
        $currency_from = $payload['currency_from']; // This is the currency buyer want to pay..
        $currency_to = $payload['currency_to']; //This is the currency merchant want
        $amount = $payload['amount']; // amount is in currency_from and this is the cost of the product
        $order_id = $payload['order_id'];
        $order_description = $payload['user_id'];

        try {
            $res = $client->request('GET', $base_url . '/v1/min-amount', [
                'query' => [
                    'currency_from' => $currency_from,
                    'currency_to' => $currency_to
                ],
                'headers' => [
                    'accept' => 'application/json',
                    'x-api-key' => $api_key,
                ],
                ]);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            echo $responseBodyAsString;
            exit;
        }
        //echo $res->getBody();
        $result['min_amount'] = json_decode($res->getBody()) -> min_amount;
        
        //Estimated amount that user need to pay in his choice of currency(currency to)
        try {
            $res = $client->request('GET', $base_url . '/v1/estimate', [
                'query' => [
                    'amount' => $amount,
                    'currency_from' => $fiat_currency,
                    'currency_to' => $currency_from
                ],
                'headers' => [
                'accept' => 'application/json',
                'x-api-key' => $api_key,
                ],
            ]);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            echo $responseBodyAsString;
            exit;
        }
        
        //echo $res->getBody();
        $result['estimated_amount'] = json_decode($res->getBody()) -> estimated_amount;
        
        // if($estimated_amount > $min_amount) {
        //   echo("You are good to go");
        // }
        // else {
        //   echo("Increase the amount please");
        //   exit;
        // }
        if($result['estimated_amount'] <= $result['min_amount']) {
            $result['completed'] = false;
            echo json_encode($result);
            exit;
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
        try {
            $res = $client->request('POST', $base_url . '/v1/payment', [
                'body' => json_encode($body),
                'headers' => [
                'accept' => 'application/json',
                'x-api-key' => $api_key,
                'Content-Type' => 'application/json'
                ],
            ]);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            echo $responseBodyAsString;
            exit;
        }
        $response = json_decode($res->getBody());
        $result['completed'] = true;
        $result['payment_id'] = $response->payment_id;
        $result['pay_address'] = $response->pay_address;
        echo json_encode($result);
        exit;
        }

        if($_GET['resource'] == "get_payment_status") {
        $payment_id = $_GET['payment_id'];

        try {
            $res = $client->request('GET', $base_url . '/v1/payment/' . $payment_id, [
                'headers' => [
                'accept' => 'application/json',
                'x-api-key' => $api_key,
                ],
            ]);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            echo $responseBodyAsString;
            exit;
        }
        $response = json_decode($res->getBody());
        $result['payment_status'] = $response->payment_status;
        $result['price_amount'] = $response->price_amount;
        $result['pay_amount'] = $response->pay_amount;
        $result['actually_paid'] = $response->actually_paid;
        echo json_encode($result);
        exit;
        }
    }

    public function NowPaymentsWebhook(Request $request) {
        $error_msg = "Unknown error";
        $auth_ok = false;
        $request_json = "";
        $request_data = null;
        if (isset($_SERVER['HTTP_X_NOWPAYMENTS_SIG']) && !empty($_SERVER['HTTP_X_NOWPAYMENTS_SIG'])) {
            $recived_hmac = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'];
            $request_json = file_get_contents('php://input');
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
        } else {
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
            if($request_data['payment_status'] == "finished") {
                $user_id = $request_data['order_description'];
                $user = User::where('id', $user_id)->first();
                $user->gamedata->Coins = (string)((int)$user->gamedata->Coins + (int)$request_data['price_amount']);
            }
        }
    }

    public function UPIGatewayCreateOrder(Request $request) {
        $json_body = file_get_contents('php://input');
        $payload = json_decode($json_body, true);
        $key = "67e4bba7-8e5d-4086-beaf-a9a2577bf670";  // Put Your key here

        $client_txn_id = RandomString();
        $body = [
            'key' => $key,
            'p_info' => "Ludo Master Topup of " . $payload['amount'],
            'amount' => $payload['amount'],
            'customer_name' => $payload['name'],
            'customer_email' => $payload['email'],
            'customer_mobile' => $payload['phone'],
            'redirect_url' => "https://sample.html",
            'client_txn_id' => $client_txn_id
        ];
        $json_body = json_encode($body);
        try {
            $response = $client->request('POST', 'https://merchant.upigateway.com/api/create_order', [
                'body' => $json_body,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            echo $responseBodyAsString;
            exit;
        }
        $response_object = json_decode($response->getBody(), true);

        if($response_object['status']) {
            $finalPayload = $response_object['data'];
            $finalPayload['client_txn_id'] = $client_txn_id;
            $finalPayload['status'] = true;
            echo(json_encode($finalPayload));
        }
        else {
            $finalPayload['status'] = false;
            echo(json_encode($finalPayload));
        }
    }

    public function RandomString() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randstring = time();
        for ($i = 0; $i < 10; $i++) {
            $randstring .= $characters[rand(0, strlen($characters))];
        }
        return $randstring;
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
        $json_body = json_encode($body);
        try {
            $response = $client->request('POST', 'https://merchant.upigateway.com/api/check_order_status', [
                'body' => $json_body,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            echo $responseBodyAsString;
            exit;
        }
        $response_object = json_decode($response->getBody(), true);

        if($response_object['status']) {
            if($response_object['data']['status'] == "success") {
                $finalPayload['upi_txn_id'] = $response_object['data']['upi_txn_id'];
                $finalPayload['customer_vpa'] = $response_object['data']['customer_vpa'];
                $finalPayload['status'] = true;
                echo(json_encode($finalPayload));
            }
            else {
                $finalPayload['status'] = false;
                echo(json_encode($finalPayload));
            }
        }
        else {
            $finalPayload['status'] = false;
            echo(json_encode($finalPayload));
        }
    }

    
}
