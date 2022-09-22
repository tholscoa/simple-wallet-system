<?php

namespace App\Http\Controllers;

use App\Http\Services\OTPService;
use App\Http\Services\UserService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request)
    {       
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'pin' => 'required|integer|min:4|max:4',
            'password' => [
                'required',
                'string',
                Password::min(6)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ]
        ]);

        //check if all validation check passed
        if ($validator->fails()) {
            Log::error($validator->errors());
            return response(['status' => false, 'message' => $validator->errors(), 'data'=>false], 422);
        }
        
        $input = $request->all();
        $first_name = trim($input['first_name']);
        $last_name = trim($input['last_name']);
        $password = trim($input['password']);
        $pin = trim($input['pin']);
        $email = $input['email'];

        //call user service to create user record
        $user = UserService::register($first_name, $last_name, $password, $email, $pin);
        
        //check if registration fail
        if(!$user[0]){
            return response()->json(['status' => false, 'message' => $user[1], 'data' => false], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        //generate OTP for user to verify email
        $otp = OTPService::generateOtp($user[1]);

        if(!$otp[0]){
            return response()->json(['status' => false, 'message' => $otp[1], 'data' => false], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        return response()->json(['status' => true, 'message' => 'A One-Time-Password has been sent to your email.', 'data' => $user[1]], Response::HTTP_CREATED);
        
    }


    public function verifyEmail(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required',
        ]);

        //check if all validation check passed
        if ($validator->fails()) {
            return response(['status' => false, 'message' => $validator->errors(), 'data'=>false], 422);
        }
        
        $input = $request->all();
        $email = $input['email'];
        $otp = trim($input['otp']);
         
        $verify = OTPService::verifyOtp($email, $otp);

        // verify otp
        if(!$verify[0]){
            return response(['status' => false, 'message' => $verify[1] , 'data'=>false], 422);
        }

        //update user record if otp verification is successful
        try{
            $user = User::where('email', $email)->first();
            $user->email_verified = true;
            $user->email_verified_at = now();
            $user->update();
        }catch(\Exception $e){
            Log::error($e);
            return response(['status' => false, 'message' => 'error encountered while updating user record.' , 'data'=>false], 422);
        }
        return response(['status' => true, 'message' => $verify[1] , 'data'=>true], 200);

    }

    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        //check if all validation check passed
        if ($validator->fails()) {
            return response(['status' => false, 'message' => $validator->errors(), 'data'=>false], 422);
        }
        
        $input = $request->all();
        $email = $input['email'];
        $password = trim($input['password']);

        $generate_key = UserService::generateUserKey($email, $password);

        if($generate_key[0] == false){
            return response(['status' => false, 'message' => $generate_key[1], 'data'=>false], 422);
        }
        return response(['status' => true, 'message' => 'Login successfull', 'data'=>['user-key'=>$generate_key[1]]], 200);
    }

}
