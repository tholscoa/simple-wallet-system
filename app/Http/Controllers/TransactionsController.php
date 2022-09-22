<?php

namespace App\Http\Controllers;

use App\Http\Services\TransferService;
use App\Http\Services\UserService;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TransactionsController extends Controller
{
    public function transfer(Request $request)
    {     
        //pass user email and password as base64 encoded through header as user-key
        $user_key = $request->header("user-key");
        if (!isset($user_key)) {
            Log::error('user key not passed');
            return response(['status' => false, 'message' => 'user-key not passed as header', 'data'=>false], 422);
        }

        //authenticate user
        $user = UserService::authenticate($user_key);
        //check if auth failed
        if($user[0] == false){
            return response(['status' => false, 'message' => 'authentication failed', 'data'=>false], 422);
        }
        

        $validator = Validator::make($request->all(), [
            'beneficiary_wallet_code' => 'required|integer',
            'amount' => 'required|integer',
            'narrative'=>'required|string',
            'pin' => 'required|integer',
        ]);

        //check if all validation check passed
        if ($validator->fails()) {
            return response(['status' => false, 'message' => $validator->errors(), 'data'=>false], 422);
        }
        
        $input = $request->all();

        $beneficiary_wallet_code = $input['beneficiary_wallet_code'];
        $amount = $input['amount'];
        $narrative = trim($input['narrative']);
        $pin = $input['pin'];

        //check transaction pin
        if(!Hash::check($pin, $user[1]->pin)){
            return response()->json(['status' => false, 'message' => 'invalid transaction pin.', 'data' => false], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $beneficiary_wallet = Wallet::where('code', $beneficiary_wallet_code)->first();
        if(empty($beneficiary_wallet)){
            return response()->json(['status' => false, 'message' => 'invalid beneficiary wallet code.', 'data' => false], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $transfer = TransferService::transfer($user[1]->id, $beneficiary_wallet->user_id, $amount, $narrative);
        if($transfer[0] == false){
            return response()->json(['status' => false, 'message' => $transfer[1], 'data' => false], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        return response()->json(['status' => true, 'message' => 'Transfer successful', 'data' => $transfer[1]], Response::HTTP_OK);
    }

    public function myHistory(Request $request){
        //pass user email and password as base64 encoded through header as user-key
        $user_key = $request->header("user-key");
        if (!isset($user_key)) {
            Log::error('user key not passed');
            return response(['status' => false, 'message' => 'user-key not passed as header', 'data'=>false], 422);
        }

        //authenticate user
        $user = UserService::authenticate($user_key);
        //check if auth failed
        if($user[0] == false){
            return response(['status' => false, 'message' => 'authentication failed', 'data'=>false], 422);
        }
        
        $my_transactions = Transaction::where('source_user_id', $user[1]->id)->orWhere('beneficiary_user_id', $user[1]->id)->get();
        return response(['status' => true, 'message' => 'Transaction history fetched', 'data'=>$my_transactions], 200);

    }
}
