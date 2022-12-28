<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; 
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\Gamedata;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    //
    public function signup(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'=>'required',
            'email' => 'required | email | unique:users,email',
            'phone' => 'required',
            'password' => 'required|confirmed',
        ], [
            'email.unique' => "User already Exists with this email address!"
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
        $user = User::create([
            "name" => $fields['name'],
            "email" => $fields['email'],
            "phone" => $fields['phone'],
            "password" => bcrypt($fields['password']),
        ]);
        
        $gamedata = Gamedata::create([
            'user_id' => $user['id'],
            'Coins' => env('JOINING_BONUS'),
        ]);

        
        if(is_null($user)) {
            $response = [
                'status' => false,
                'message' => 'there is a problem creating user'
            ];
            return response()->json($response, 500);
        }

        event(new Registered($user));

        $response = [
            'status' => true,
            'message' => "User Registeration success. Please verify your email now.",
            'user_id' => $user->id
        ];
        return response()->json($response, 201);
    }

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required | email',
            'password'=>'required',
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
        $user = User::where('email', $fields['email'])->first();

        if(is_null($user)) {
            $response = [
                'status' => false,
                'message' => "Username or password is incorrect !"
            ];
            return response()->json($response, 400);
        }
        if(!Hash::check($fields['password'], $user->password)) {
            $response = [
                'status' => false,
                'message' => "Username or password is incorrect !"
            ];
            return response()->json($response, 400);
        }

        if(!$user->hasVerifiedEmail()) {
            $response = [
                'status' => false,
                'message' => "User Email is not verified. Please verify the email."
            ];
            return response()->json($response, 400);
        }

        $token = $user->createToken('Ludo7Master')->plainTextToken;

        $response = [
            'status' => true,
            'token' => $token,
            'user_id' => $user->id,
            'message' => "User is logged in now."
        ];
        return response()->json($response, 200);
    }

    public function logout(Request $request) {
        $request->user()->tokens()->delete();
        $response = [
            "status" => true,
            "message" => "Logged Out",
        ];
        return response($response, 200);
    }

    public function forgot(Request $request) {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request['email'])->first();
        if(is_null($user)) {
            $response = [
                'status' => false,
                'message' => "User not found with this email !"
            ];
            return response()->json($response, 400);
        }
        $user->tokens()->delete();

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            $response = [
                "status" => true,
                "message" => "Password reset Link is sent to your email",
            ];
            return response($response, 200);
        }

    }
    
    public function reset(Request $request) {

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);
        $user = $request-> user();
        $user->tokens()->delete();
        $token = $user->createToken('Ludo7Master')->plainTextToken;
        $response = [
            "status" => true,
            "message" => "Password Updated",
            "token" => $token,
        ];
        return response($response, 200);
    }

     // This function is called when the client sends an access token to your server for authentication.
    public function loginWithFacebook(Request $request)
    {
        $fields = $request->validate([
            'access_token' => 'required'
        ]);
        $accessToken = $fields['access_token'];

        $access_token_server_url = 'https://graph.facebook.com/oauth/access_token?client_id='.env("FB_CLIENT_ID").'&client_secret='.env("FB_CLIENT_SECRET").'&grant_type=client_credentials';
        $access_token_server_response = file_get_contents($access_token_server_url);
        $access_token_server = json_decode($access_token_server_response, true)['access_token'];

        // Send a GET request to the Facebook Graph API to validate the access token.
        $validationUrl = 'https://graph.facebook.com/debug_token?input_token=' . $accessToken . '&access_token=' . $access_token_server;
        $validationResponse = file_get_contents($validationUrl);
        $validationData = json_decode($validationResponse, true);

        // Check if the access token is valid.
        if ($validationData['data']['is_valid']) {
            // The access token is valid.

            // Get the user's Facebook ID.
            $facebookId = $validationData['data']['user_id'];

            // Find the user in your database.
            $user = User::where('facebook_id', $facebookId)->first();
            if ($user) {
                // The user was found in the database.

                // Generate an access token for the user.
                $token = $user->createToken('authToken')->plainTextToken;

                // Return the access token to the client.
                return response()->json(['access_token' => $token], 200);
            } else {
                // The user was not found in the database.

                // Send a GET request to the Facebook Graph API to fetch the user
                $profileUrl = 'https://graph.facebook.com/me?access_token=' . $accessToken;
                $profileResponse = file_get_contents($profileUrl);
                $profileData = json_decode($profileResponse, true);
                 // The user was not found in the database.
                $user = new User();
                $user->name = $profileData['name'];
                
                if(!empty($profileData['email']))
                    $user->email = $profileData['email'];
                
                $user->facebook_id = $profileData['id'];
                $user->save();

                $token = $user->createToken('authToken')->plainTextToken;
                return response()->json(['access_token' => $token], 200);
            }
        } else {
            // The access token is not valid.
            return response()->json(['error' => 'Invalid access token'], 401);
        }
    }

    public function linkFacebookAccount(Request $request) {         //will be in sanctum middleware
        $fields = $request->validate([
            'access_token' => 'required',   //provided by facebook sdk
            'force_link' => 'required'      //If another user is already linked to the account, unlink the other user and re-link.  Rarely used
        ]);
        $accessToken = $request['access_token'];
        $validationUrl = 'https://graph.facebook.com/debug_token?input_token=' . $accessToken . '&access_token=' . config(env('FB_CLIENT_ID')) . '|' . config(env('FB_CLIENT_SECRET'));
        $validationResponse = file_get_contents($validationUrl);
        $validationData = json_decode($validationResponse, true);

        if ($validationData['data']['is_valid']) {
            $facebookId = $validationData['data']['user_id'];
            $user = User::where('facebook_id', $facebookId)->first();
            if ($user) {
                if($fields['force_link']) {
                    $user->facebook_id = null;
                    $user->save();
                    $user = $request->user();
                    $user->facebook_id = $facebookId;

                    $response = [
                        'status' => true,
                        'message' => "Facebook account is successfully linked"
                    ];
                    return response()->json($response, 200);
                }
                else {
                    $response = [
                        'status' => false,
                        'message' => "This account is already linked to an existing account"
                    ];
                    return response()->json($response, 401);
                }
            }
            else {
                $user = $request->user();
                $user->facebook_id = $facebookId;

                $response = [
                    'status' => true,
                    'message' => "Facebook account is successfully linked"
                ];
                return response()->json($response, 200);
            }
        }
        else {
            $response = [
                'status' => false,
                'message' => "Invalid access token"
            ];
            return response()->json($response, 401);
        }
    }

    public function loginWithCustomID(Request $request) {
        $fields = $request->validate([
            'custom_id' => 'required'
        ]);

        $user = User::where('custom_id', $fields['custom_id'])->first();
        if(is_null($user)) {
            $name = "User ". ((User::all()->count() == 0 ? 0 : User::all()->last()->id) + 1);
            $custom_id = $fields['custom_id'];
            $user = User::createUser($name, null, null, null, null, $custom_id);
        }
        $token = $user->createToken('guest')->plainTextToken;

        $response = [
            'status' => true,
            'token' => $token,
            'user' => $user
        ];
        return response()->json($response, 201);
    }
}
