<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class FriendsController extends Controller
{
    public function addFriend(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'force' => 'required' 
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
        $user = $request->user();
        $recipient =  User::where('id', $fields['user_id'])->first();
        if(is_null($recipient)) {
            $response = [
                'status' => false,
                'message' => 'No User exists with this id'
            ];
            return response()->json($response, 400);
        }
        $user->befriend($recipient);
        if($fields['force'] == "true") {
            $recipient->acceptFriendRequest($user);
            $response = [
                'status' => true,
                'message' => 'user added as a friend'
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status' => true,
            'message' => 'sent request to the user'
        ];
        return response()->json($response, 200);
    }

    public function acceptFriend(Request $request) {
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required',
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
        $user = $request->user();
        $sender = User::where('id', $fields['sender_id'])->first();
        if(is_null($sender)) {
            $response = [
                'status' => false,
                'message' => 'No request exists from this user'
            ];
            return response()->json($response, 400);
        }
        $user->acceptFriendRequest($sender);
        $response = [
            'status' => true,
            'message' => 'Request accepted'
        ];
        return response()->json($response, 200);
    }

    public function denyFriend(Request $request) {
        $fields = $request->validate([
            'sender_id' => 'required'
        ]);
        $user = $request->user();
        $sender = User::where('id', $fields['sender_id'])->first();
        if(is_null($sender)) {
            $response = [
                'status' => false,
                'message' => 'No user exists for this id'
            ];
            return response()->json($response, 400);
        }
        $user->denyFriendRequest($sender);
        $response = [
            'status' => true,
            'message' => 'Request denied'
        ];
        return response()->json($response, 200);
    }

    public function removeFriend(Request $request) {
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required',
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
        $user = $request->user();
        $sender = User::where('id', $fields['friend_id'])->first();
        if(is_null($sender)) {
            $response = [
                'status' => false,
                'message' => 'No user exists for this id'
            ];
            return response()->json($response, 400);
        }
        $user->unfriend($sender);
        $response = [
            'status' => true,
            'message' => 'User removed from friends'
        ];
        return response()->json($response, 200);
    }

    public function getFriends(Request $request) {
        $user = $request->user();
        $users = $user->getFriends($perPage = 0, $group_name = '', $fields = ['id','name']);
        $friends = array();
        foreach($users as $player) {
            array_push($friends, ['id' => $player['id'], 'name' => $player['name']]);
        }
        $response = [
            'status' => true,
            'message' => "List of friends fetched",
            'friends' => $friends
        ];
        return response()->json($response, 200);
    }
}
