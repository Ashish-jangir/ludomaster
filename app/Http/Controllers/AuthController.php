<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; 
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\Gamedata;


class AuthController extends Controller
{
    //
    public function signup(Request $request) {
        $fields = $request->validate([
            'name'=>'required',
            'email' => 'required | email | unique:users,email',
            'phone' => 'required',
            'password' => 'required|confirmed',
        ]);
        
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
                'msg' => 'there is a problem creating user'
            ];
            return response()->json($response, 500);
        }

        event(new Registered($user));
        // $token = $user->createToken('Ludo7Master')->plainTextToken;

        $response = [
            'status' => true,
            // 'token' => $token,
            'user' => $user
        ];
        return response()->json($response, 201);
    }

    public function login(Request $request) {
        $fields = $request->validate([
            'email' => 'required | email',
            'password'=>'required',
        ]);

        $user = User::where('email', $fields['email'])->first();

        if(is_null($user)) {
            $response = [
                'status' => false,
                'msg' => "Username or password is incorrect !"
            ];
            return response()->json($response, 400);
        }
        if(!Hash::check($fields['password'], $user->password)) {
            $response = [
                'status' => false,
                'msg' => "Username or password is incorrect !"
            ];
            return response()->json($response, 400);
        }

        if(!$user->hasVerifiedEmail()) {
            $response = [
                'status' => false,
                'msg' => "User Email is not verified. Please verify the email."
            ];
            return response()->json($response, 400);
        }

        $token = $user->createToken('Ludo7Master')->plainTextToken;

        $response = [
            'status' => true,
            'token' => $token,
            'user' => $user
        ];
        return response()->json($response, 200);
    }

    public function logout(Request $request) {
        $request->user()->tokens()->delete();
        $response = [
            "status" => true,
            "msg" => "Logged Out",
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
                'msg' => "User not found with this email !"
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
                "msg" => "Password reset Link is sent to your email",
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
            "msg" => "Password Updated",
            "token" => $token,
        ];
        return response($response, 200);
    }
}
