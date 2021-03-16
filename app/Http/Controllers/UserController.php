<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller {

    public function __construct()
    {
        //$this->middleware('auth:api', ['except' => ['register']]);
        //$this->middleware('auth:api');
    }

    public function checkUsername($username) {
        if($user = User::where('username', $username)->first()) {
            return response()->json(['available' => false], 200);
        } else {
            return response()->json(['available' => true], 200);
        }
    }

    public function setAndSendCode(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email'
        ]);

        if($validator->fails()) {
            return response()->json(['frontendMessage' => $validator->errors()->first()], 422);
        }

        $user = User::where('email', $request->email)->first();
        $user->code_attempts = 0;
        $user->code = strval(rand(0,9)) . strval(rand(0,9)) . strval(rand(0,9)) . strval(rand(0,9)) . strval(rand(0,9)) . strval(rand(0,9));
        $user->save();

        Mail::send([], [], function ($message) use($user) {
            $message->to($user->email)
                ->subject($user->code . ' - Your Yaywork code')
                ->setBody('
                <p>Please enter code <strong>' . $user->code . '</strong> to continue.</p>
                <p>Email: ' . $user->email . '<br/>Username: ' . $user->username .'<br/>Password: (encrypted)</p>
                <p>Ok thx bye.</p>'
                , 'text/html');
        });

        return response()->json(['message' => 'success'], 200);
        
    }

    public function verifyCode(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required|min:6|max:6',
            'email' => 'required|sometimes|nullable|exists:users,email'
        ]);

        if($validator->fails()) {
            return response()->json(['frontendMessage' => $validator->errors()->first()], 422);
        }

        if(!($user = auth()->user())) {
            if(!($user = User::where('email', $request->email)->first())) {
                return response()->json(['frontendMessage' => 'User not found.'], 404);
            }
        } 
        
        if($user->code_attempts >= 5) {
            return response()->json(['frontendMessage' => 'Too many attempts.'], 422);
        } else if($user->code === $request->code) {
            $user->code_attempts = 0;
            $user->save();
            return response()->json(['message' => 'Valid code.'], 200);
        } else {
            $user->code_attempts += 1;
            $user->save();
            return response()->json(['frontendMessage' => 'Wrong code. ' . (5 - $user->code_attempts) . ' attempt(s) remaining.'], 422);
        }
    }

    public function confirm(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required|min:6|max:6'
        ]);

        if($validator->fails()) {
            return response()->json(['frontendMessage' => $validator->errors()->first()], 422);
        }

        $user = auth()->user();
        if($user->code_attempts >= 5) {
            return response()->json(['frontendMessage' => 'Too many attempts.'], 422);
        } else if($user->code === $request->code) {
            $user->code = null;
            $user->code_attempts = 0;
            $user->confirmed = 1;
            $user->save();
            return $user;
        } else {
            return response()->json(['frontendMessage' => 'Wrong code, yo.'], 422);
        }
    }

    public function updatePassword(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required|min:6|max:6',
            'email' => 'required|sometimes|email|max:191|exists:users',
            'password' => 'required|confirmed|min:6|max:191'
        ]);

        if($validator->fails()) {
            return response()->json(['frontendMessage' => $validator->errors()->first()], 422);
        }

        if(!($user = auth()->user())) {
            if(!($user = User::where('email', $request->email)->first())) {
                return response()->json(['frontendMessage' => 'User not found.'], 404);
            }
        } 

        if($user->code_attempts >= 5) {
            return response()->json(['frontendMessage' => 'Too many attempts.'], 422);
        } else if($user->code === $request->code) {
            $user->code = null;
            $user->code_attempts = 0;
            $user->confirmed = 1;
            $user->password = Hash::make($request->password);
            $user->save();
            return $user;
        } else {
            return response()->json(['frontendMessage' => 'Wrong code, yo.'], 422);
        }
    }

    public function create(Request $request) {

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'username' => 'required|alpha_dash|min:3|max:15|unique:users',
            'email' => 'required|email|max:191|unique:users',
            'password' => 'required|confirmed|min:6|max:191'
        ]);

        if($validator->fails()) {
            return response()->json(['frontendMessage' => $validator->errors()->first()], 422);
        }

        $user = new User();
        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->code = strval(rand(0,9)) . strval(rand(0,9)) . strval(rand(0,9)) . strval(rand(0,9)) . strval(rand(0,9)) . strval(rand(0,9));
        $user->save();

        Mail::send([], [], function ($message) use($user) {
            $message->to($user->email)
                ->subject($user->code . ' - Your Yay Work code')
                ->setBody('
                <p>Hi! Welcome to Yay Work!</p>
                <p>Please enter code <strong>' . $user->code . '</strong> to continue.</p>
                <p>Email: ' . $user->email . '<br/>Username: ' . $user->username .'<br/>Password: (encrypted)</p>
                <p>Ok thx bye.</p>'
                , 'text/html');
        });
        
        return $user;
    }

}