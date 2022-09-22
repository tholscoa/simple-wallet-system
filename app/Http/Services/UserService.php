<?php

namespace App\Http\Services;

use App\Models\OneTimePassword;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService 
{
    public static function register($first_name, $last_name, $password, $email, $pin){
        //check if account number already profiled for mobile
        $exist = User::where('email', $email)->first();
        if($exist){
            if($exist->email_verified != true){
                //return user record if user exist and email is not yet verified
                return [true, $exist];
            }
            //return a already register message if user has registered and verified email
            Log::error($email . " already registered, kindly go to login");
            return [false, "User already registered, kindly go to login"];
        }

        //encrypt user password and pin
        $encrypted_password = Hash::make(trim($password));
        $encrypted_pin = Hash::make(trim($pin));

        try {
            // create user record
            $user = new User;
            $user->first_name = $first_name;
            $user->last_name = $last_name;
            $user->email = $email;
            $user->password = $encrypted_password;
            $user->pin = $encrypted_pin;
            $user->status = true;
            $user->save();              
        } catch (\Exception $e) {
            //if saving failed, log error and return error
            Log::error("Error occurred while creating  account with email " . $email . '. Error details '. json_encode($e));
            return [false, "Error occur while creating this account"];
        }
        return [true, $user];
    }

    public static function resendOtp($email){
        $user = User::where('email', $email)->first();

        if(!$user){
            return [false, 'user not found'];
        }

        $otp = OTPService::generateOtp($user);

        if(!$otp[0]){
            return [false, $otp[1]];
        }

        return [true, $user->email];
    }

    public static function createWallet($user){
        // check if wallet already exist for user
        $existing_wallet = Wallet::whereUserId($user->id)->first();

        // if wallet exist return wallet with true response
        if(!empty($existing_wallet)){
            return [true, $existing_wallet];
        }

        //else create new wallet for user
        $generated_wallet_code = rand(1000000000, 9999999999);
        $wallet = new Wallet();
        $wallet->user_id = $user->id;
        $wallet->code = $generated_wallet_code;
        $wallet->balance = 0.00;
        $wallet->status = true;

        try{
            $wallet->save();
        }catch(\Exception $e){
            // if it failed log why
            Log::error($e);
            return [false, 'unable to create wallet'];
        }

        return [true, $wallet];
    }
    
    public static function authenticate($key){
        $header_received = base64_decode($key);
        $email = explode(":", $header_received)[0];
        $password = explode(":", $header_received)[1];

        // check if user exist
        $user = User::whereEmail($email)->first();
        if(empty($user)){
            return [false, 'user not found'];
        }

        //check if user credentials is correct
        if(!Hash::check($password, $user->password)){
            return [false, 'authentication failed'];
        }

        return [true, $user];
    } 

    public static function generateUserKey($email, $password){
        $user_key = base64_encode($email . ":" . $password);
        $validate = self::authenticate($user_key);
        if($validate[0] == false){
            return [false, $validate[1]];
        }
        return [true, $user_key];
    }
}
