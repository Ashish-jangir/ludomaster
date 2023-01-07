<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class GeneralRequestController extends Controller
{
    public function sendMessage(Request $request) {
        $validator = Validator::make($request->all(), [
            'sms' => 'required'
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
        $sms = $validator->validated()['sms'];
        $user = $request->user();
        $record = Message::create([
            'sms' => $sms,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'avatar_index' => $user->gamedata->AvatarIndex == null ? "0" : $user->gamedata->AvatarIndex
        ]);
        if(is_null($record)) {
            $response = [
                'status' => false,
                'message' => 'There is a problem in sending message'
            ];
            return response()->json($response, 500);
        }

        $response = [
            'status' => true,
            'message' => 'Message sent successfully'
        ];
        return response()->json($response, 201);
    }

    public function getMessages(Request $request) {
        $user = $request->user();
        $messages = Message::all();
        
        if(is_null($messages)) {
            $response = [
                'status' => false,
                'message' => 'There are no messages'
            ];
            return response()->json($response, 404);
        }
        
        $response = [
            'status' => true,
            'messages' => $messages->toArray(),
        ];
        return response()->json($response, 201);
    }
}
