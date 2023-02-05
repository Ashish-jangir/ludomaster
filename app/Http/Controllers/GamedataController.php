<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gamedata;
use App\Models\User;
use App\Models\WebhookResponses;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GamedataController extends Controller
{
    //
    public function getUserData(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required', 
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
        $fields = $request;
        $user = User::where('id', $fields['user_id'])->first();
        $gamedata = Gamedata::where('user_id', $user['id'])->first();
        if($request->user()['id'] == $user['id']) {
            $keys = $gamedata->toArray();
            $keys['PlayerName'] = $user['name'];
            $keys['Email'] = $user['email'];
            $keys['MobileNumber'] = $user['phone'];
            $keys['FbId'] = $user['facebook_id'];
            $response = [
                'status' => true,
                'message' => 'User data fetched',
                'data' => $keys,
            ];
            return (response()->json($response, 200));
        }
        else {
            $data['id'] = $user['id'];
            $data['PlayerName'] = $user['name'];
            $data['PlayerAvatarUrl'] = $gamedata['PlayerAvatarUrl'];
            $data['GamesPlayed'] = $gamedata['GamesPlayed'];
            $data['AvatarIndex'] = $gamedata['AvatarIndex'];
            $data['FourPlayerWins'] = $gamedata['FourPlayerWins'];
            $data['TwoPlayerWins'] = $gamedata['TwoPlayerWins'];
            $data['LoggedType'] = $gamedata['LoggedType'];
            $response = [
                'status' => true,
                'message' => 'User data fetched',
                'data' => $data,
            ];
            return (response()->json($response, 200));
        }
    }

    public function updateUserData(Request $request) {
        $validator = Validator::make($request->all(), [
            'Mobile Number' => 'numeric',
            'Coins' => 'numeric',
            'GamesPlayed' => 'numeric',
            'FourPlayerWins' => 'numeric',
            'PrivateTableWins' => 'numeric',
            'TotalEarnings' => 'numeric',
            'TwoPlayerWins' => 'numeric',
            'LoggedType' =>  Rule::in(['Guest', 'EmailAccount', 'Facebook']),
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
        $user = $request->user();
        $user->name = is_null($request['PlayerName']) ? $user->name : $request['PlayerName'];
        $user->phone = is_null($request['MobileNumber']) ? $user->phone : $request['MobileNumber'];
        $user->email = is_null($request['Email']) ? $user->email : $request['Email'];
        $user->save();

        $gamedata = $user->gamedata;
        $gamedata->Reffer = is_null($request['Reffer']) ? $gamedata->Reffer : $request['Reffer'];
        $gamedata->PlayerAvatarUrl = is_null($request['PlayerAvatarUrl']) ? $gamedata->PlayerAvatarUrl : $request['PlayerAvatarUrl'];
        $gamedata->FortuneWheelLastFreeTime = is_null($request['FortuneWheelLastFreeTime']) ? $gamedata->FortuneWheelLastFreeTime : $request['FortuneWheelLastFreeTime'];
        $gamedata->Chats = is_null($request['Chats']) ? $gamedata->Chats : $request['Chats'];
        $gamedata->Emoji = is_null($request['Emoji']) ? $gamedata->Emoji : $request['Emoji'];
        $gamedata->Coins = is_null($request['Coins']) ? $gamedata->Coins : $request['Coins'];
        $gamedata->GamesPlayed = is_null($request['GamesPlayed']) ? $gamedata->GamesPlayed : $request['GamesPlayed'];
        $gamedata->AvatarIndex = is_null($request['AvatarIndex']) ? $gamedata->AvatarIndex : $request['AvatarIndex'];
        $gamedata->FourPlayerWins = is_null($request['FourPlayerWins']) ? $gamedata->FourPlayerWins : $request['FourPlayerWins'];
        $gamedata->LoggedType = is_null($request['LoggedType']) ? $gamedata->LoggedType : $request['LoggedType'];
        $gamedata->PrivateTableWins = is_null($request['PrivateTableWins']) ? $gamedata->PrivateTableWins : $request['PrivateTableWins'];
        $gamedata->TitleFirstLogin = is_null($request['TitleFirstLogin']) ? $gamedata->TitleFirstLogin : $request['TitleFirstLogin'];
        $gamedata->TotalEarnings = is_null($request['TotalEarnings']) ? $gamedata->TotalEarnings : $request['TotalEarnings'];
        $gamedata->TwoPlayerWins = is_null($request['TwoPlayerWins']) ? $gamedata->TwoPlayerWins : $request['TwoPlayerWins'];
        $gamedata->save();
        $response = [
            'status' => true,
            'message' => "User data update successfull"
        ];
        return response()->json($response, 200);
    }

    public function authForPhoton(Request $request) {
        if(empty($request['id']) || empty($request['token']) ) {
            $response = [
                'ResultCode' => 3,
                'Message' => "Invalid parameters."
            ];
            return response()->json($response, 400);
        }

        $user = User::where('id', $request['id'])->first();
        if(is_null($user)) {
            $response = [
                'ResultCode' => 2,
                'Message' => "Authentication failed. No User Exists with this id."
            ];
            return response()->json($response, 400);
        }
        if(!Hash::check($request['token'], $user->gamedata->PhotonToken)) {
            $response = [
                'ResultCode' => 2,
                'Message' => "Authentication failed. Wrong Photon Token."
            ];
            return response()->json($response, 400);
        }    

        $response = [
            'ResultCode' => 1,
            'UserId' => $user->id
        ];
        return response()->json($response, 200);
    }

    public function getPhotonToken(Request $request) {
        $user = $request->user();
        $token = $user->id. "|".sha1(time());
        $user->gamedata->PhotonToken = bcrypt($token);
        $user->gamedata->save();
        $response = [
            'status' => true,
            'token' => $token,
            'user' => $user->id
        ];
        return response()->json($response, 200);
    }
}
