<?php

namespace App\Http\Services;

use App\Models\OneTimePassword;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TransactionService 
{
    public static function createTransactionRecord($source_user_id, $beneficiary_user_id, $reference_no, $transaction_id, $narration, $status, $other=''){
        $data = [
            'source_user_id' => $source_user_id,
            'beneficiary_user_id' => $beneficiary_user_id,
            'reference_no' => $reference_no,
            'transaction_id' => $transaction_id,
            'narration' => $narration,
            'status' => $status,
            'other' => $other          
        ];

        try{
            Transaction::create($data);
        }catch(\Exception $e){
            Log::error('error occurred while creating transaction record - '. $e);
            return false;
        }
        return true;

    }
}
