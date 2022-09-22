<?php

namespace App\Http\Services;

use App\Models\OneTimePassword;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TransferService 
{
    public static function creditDebitUser($code, $action, $amount){
        $wallet = Wallet::whereCode($code)->first();
        if(empty($wallet)){
            return [false, 'invalid wallet'];
        }

        $user = User::whereId($wallet->user_id)->first();
        if(empty($user)){
            return [false, 'user not found'];
        } 

        $balance = $wallet->balance;
        if($action == 'credit' || $action == 'debit'){
            if($action == 'credit'){
                try{
                    $wallet->balance = $balance + $amount;
                    $wallet->update();
                    $body = "your balance was credited with $amount Tokens";
                    //send notification to user
                    NotificationService::Email($user->email, $body);
                }catch(\Exception $e){
                    Log::error($e);
                    return [false, 'unsuccessful'];
                }
                return [true, $wallet->balance];
            }else if($action == 'debit'){
                if($wallet->balance < $amount){
                    return [false, 'insufficient balance'];
                }
                try{
                    $wallet->balance = $balance - $amount;
                    $wallet->update();
                }catch(\Exception $e){
                    Log::error($e);
                    return [false, 'unsuccessful'];
                }
                $body = "your balance was debited with $amount Tokens";
                NotificationService::Email($user->email, $body);
                Log::error($body);
                return [true, $wallet->balance];
            }
        }else{
            return [false, 'invalid action'];
        }
    }

    public static function transfer($source_user_id, $beneficiary_user_id, $amount, $narrative=''){
        
        $ref_no = 'TRX-'. time() . '-' . $source_user_id . '|'. $beneficiary_user_id;
        $trans_id = time().$source_user_id;
        
        //check if it same wallet
        if($source_user_id == $beneficiary_user_id){
            //create transaction record
            TransactionService::createTransactionRecord($source_user_id, $beneficiary_user_id, $ref_no, $trans_id, $narrative, false, 'same wallet transfer');
            return [false, 'Cannot transfer into same wallet'];
        }
        
        $source = Wallet::where('user_id', $source_user_id)->first();
        $beneficiary = Wallet::where('user_id', $beneficiary_user_id)->first();
        if(empty($source)){
            //create transaction record
            TransactionService::createTransactionRecord($source_user_id, $beneficiary_user_id, $ref_no, $trans_id, $narrative, false, 'invalid source wallet');
            return [false, 'invalid source wallet'];
        }

        if(empty($beneficiary)){
            //create transaction record
            TransactionService::createTransactionRecord($source_user_id, $beneficiary_user_id, $ref_no, $trans_id, $narrative, false, 'invalid beneficiary wallet');
            return [false, 'invalid beneficiary wallet'];
        }

        if($source->balance < $amount){
            //create transaction record
            TransactionService::createTransactionRecord($source_user_id, $beneficiary_user_id, $ref_no, $trans_id, $narrative, false, 'insufficient balance');
            return [false, 'insufficient balance'];
        }

        $source->balance = $source->balance - $amount;

        $beneficiary->balance = $beneficiary->balance + $amount;
        try{
            $source->update();
            $beneficiary->update();
        }catch(\Exception $e){
            Log::error($e);
            //create transaction record
            TransactionService::createTransactionRecord($source_user_id, $beneficiary_user_id, $ref_no, $trans_id, $narrative, false, 'Something went wrong, please contact admin');
            return [false, 'Something went wrong, please contact admin'];
        }
        
        //create transaction record
        TransactionService::createTransactionRecord($source_user_id, $beneficiary_user_id, $ref_no, $trans_id, $narrative, true, 'transfer successful');
        $result = [
            "reference number" =>  $ref_no,
            "transaction id" =>  $trans_id,
            
        ];
        return [true, $result];
    
    }
}
