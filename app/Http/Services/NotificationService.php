<?php

namespace App\Http\Services;

use App\Mail\NotificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public static function Email($recipient_email, $body){
        $user = User::whereEmail($recipient_email)->first();
        
        $details = [
            'title' => "Mail from wallet",
            'body' =>$body
        ];
        try{
            Mail::to($user->email)->send(new NotificationMail($details));
        }catch(\Exception $e){
            Log::error($e);
            return false;
        }
        return true;
    }
}
