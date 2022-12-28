<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gamedata;
use App\Models\User;
use App\Models\WebhookResponses;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash; 


class GamedataController extends Controller
{
    //
    public function getUserData(Request $request) {
        $user = $request->user();
        $gamedata = Gamedata::where('user_id', $user['id'])->first();
        $keys = $gamedata->toArray();
        $keys['PlayerName'] = $user['name'];
        $keys['Email'] = $user['email'];
        $keys['Mobile Number'] = $user['phone'];
        return (response()->json($keys, 200));
    }

    public function updateUserData(Request $request) {
        $user = $request->user();
        $user->name = empty($request['PlayerName']) ? $user->name : $request['PlayerName'];
        $user->phone = empty($request['Mobile Number']) ? $user->phone : $request['Mobile Number'];
        $user->save();

        $gamedata = $user->gamedata;
        $gamedata->Reffer = empty($request['Reffer']) ? $gamedata->Reffer : $request['Reffer'];
        $gamedata->PlayerAvatarUrl = empty($request['PlayerAvatarUrl']) ? $gamedata->PlayerAvatarUrl : $request['PlayerAvatarUrl'];
        $gamedata->FortuneWheelLastFreeTime = empty($request['FortuneWheelLastFreeTime']) ? $gamedata->FortuneWheelLastFreeTime : $request['FortuneWheelLastFreeTime'];
        $gamedata->Chats = empty($request['Chats']) ? $gamedata->Chats : $request['Chats'];
        $gamedata->Emoji = empty($request['Emoji']) ? $gamedata->Emoji : $request['Emoji'];
        $gamedata->Coins = empty($request['Coins']) ? $gamedata->Coins : $request['Coins'];
        $gamedata->GamesPlayed = empty($request['GamesPlayed']) ? $gamedata->GamesPlayed : $request['GamesPlayed'];
        $gamedata->AvatarIndex = empty($request['AvatarIndex']) ? $gamedata->AvatarIndex : $request['AvatarIndex'];
        $gamedata->FourPlayerWins = empty($request['FourPlayerWins']) ? $gamedata->FourPlayerWins : $request['FourPlayerWins'];
        $gamedata->LoggedType = empty($request['LoggedType']) ? $gamedata->LoggedType : $request['LoggedType'];
        $gamedata->PrivateTableWins = empty($request['PrivateTableWins']) ? $gamedata->PrivateTableWins : $request['PrivateTableWins'];
        $gamedata->TitleFirstLogin = empty($request['TitleFirstLogin']) ? $gamedata->TitleFirstLogin : $request['TitleFirstLogin'];
        $gamedata->TotalEarnings = empty($request['TotalEarnings']) ? $gamedata->TotalEarnings : $request['TotalEarnings'];
        $gamedata->TwoPlayerWins = empty($request['TwoPlayerWins']) ? $gamedata->TwoPlayerWins : $request['TwoPlayerWins'];
        $gamedata->save();
        $response = [
            'status' => true,
            'message' => "User data update successfull"
        ];
        return response()->json($response, 200);
    }

    public function authForPhoton(Request $request) {
        WebhookResponses::create([
            'response' => json_encode($request->query)
        ]);
        if(empty($request['email']) || empty($request['token']) ) {
            $response = [
                'ResultCode' => 3,
                'Message' => "Invalid parameters."
            ];
            return response()->json($response, 200);
        }

        $user = User::where('email', $request['email'])->first();
        if(is_null($user)) {
            $response = [
                'ResultCode' => 2,
                'Message' => "Authentication failed. Wrong credentials."
            ];
            return response()->json($response, 200);
        }
        if(!Hash::check($request['token'], $user->gamedata->PhotonToken)) {
            $response = [
                'ResultCode' => 2,
                'Message' => "Authentication failed. Wrong credentials."
            ];
            return response()->json($response, 200);
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
