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
}
