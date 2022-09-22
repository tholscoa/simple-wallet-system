<?php

namespace App\Http\Services;

use App\Models\OneTimePassword;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OTPService 
{
    public static function generateOtp($user){
        $myotp = rand(1000, 9999);

        //check if user have unused otp. if yes change OTP to used and proceed to generate a new one
        $existingOtp = OneTimePassword::whereUserId($user->id)->whereUsed(false)->first();
        if($existingOtp){
            $existingOtp->used = true;
            $existingOtp->update();
        }

        //create new otp record
        $otp = new OneTimePassword;
        $otp->user_id = $user->id;
        $otp->otp = Hash::make($myotp);
        $otp->used = false;
        //otp will expire in 10minutes from when it was created
        $otp->expired_at = Carbon::now()->addMinutes(10)->timestamp;
        try{
            $otp->save();
            $message = "Your One-Time-Password is: ". $myotp;

            //sen OTP to email
            $send_sms = NotificationService::Email($user->email, $message);
            if(!$send_sms){
                Log::error("Error occur while sending OTP to user");
                return [false, 'error generating OTP.'];
            }
        }catch(\Exception $e){
            Log::error('error generating OTP.'. $e);
            return [false, 'error generating OTP.'];
        }
        return [true, $myotp];
    }

    public static function verifyOtp($email, $otp){

        $user = User::where('email', $email)->first();
        if(!$user){
            return [false, 'user not found'];
        }
        $myotp = OneTimePassword::whereUserId($user->id)->whereUsed(false)->first();

        //check if otp exist
        if(!$myotp){
            //generate new otp for user if no otp found
            self::generateOtp($user);
            return [false, 'no OTP found for user. New OTP sent to your mail'];
        }
        // check if otp has expired send new otp if otp has expired
        if((Carbon::now()->timestamp) > ($myotp->expired_at)){
            //update otp to used and generate new otp for user
            $myotp->used = true;
            $myotp->update();
            $user = User::where('email', $email)->first();
            self::generateOtp($user);
            return [false, 'Expired Token. New OTP has been sent to your phone'];
        }
        //check if otp entered is correct
        if(!Hash::check($otp, $myotp->otp)){
            return [false, 'Invalid Token'];
        }

        //update otp 
        try{
            $myotp->used = true;
            $myotp->update();
        }catch(\Exception $e){
            //if otp update failed return error message and log why it failed
            Log::error($e);
            return [false, 'error encountered while updating otp'];
        }

        
        return [true, 'Otp verified successfully'];
    }
}
