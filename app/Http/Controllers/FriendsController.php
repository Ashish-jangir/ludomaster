<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class FriendsController extends Controller
{
    //
    public function addFriend(Request $request) {
        $fields = $request->validate([
            'user_id' => 'required'
        ]);
        $user = $request->user();
        $recipient = User::where('id', $fields['user_id'])->first();
        if(is_null($recipient)) {
            $response = [
                'status' => false,
                'msg' => 'No User exists with this id'
            ];
            return response()->json($response, 400);
        }
        $user->befriend($recipient);
        $response = [
            'status' => true,
            'msg' => 'sent request to the user'
        ];
        return response()->json($response, 200);
    }

    public function acceptFriend(Request $request) {
        $fields = $request->validate([
            'sender_id' => 'required'
        ]);
        $user = $request->user();
        $sender = User::where('id', $fields['sender_id'])->first();
        if(is_null($sender)) {
            $response = [
                'status' => false,
                'msg' => 'No request exists from this user'
            ];
            return response()->json($response, 400);
        }
        $user->acceptFriendRequest($sender);
        $response = [
            'status' => true,
            'msg' => 'Request accepted'
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
                'msg' => 'No user exists for this id'
            ];
            return response()->json($response, 400);
        }
        $user->denyFriendRequest($sender);
        $response = [
            'status' => true,
            'msg' => 'Request denied'
        ];
        return response()->json($response, 200);
    }

    public function removeFriend(Request $request) {
        $fields = $request->validate([
            'friend_id' => 'required'
        ]);
        $user = $request->user();
        $sender = User::where('id', $fields['friend_id'])->first();
        if(is_null($sender)) {
            $response = [
                'status' => false,
                'msg' => 'No user exists for this id'
            ];
            return response()->json($response, 400);
        }
        $user->unfriend($sender);
        $response = [
            'status' => true,
            'msg' => 'User removed from friends'
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
            'friends' => $friends
        ];
        return response()->json($response, 200);
    }
}
