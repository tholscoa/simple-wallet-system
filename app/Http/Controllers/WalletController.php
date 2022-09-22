<?php

namespace App\Http\Controllers;

use App\Http\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function create(Request $request)
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

        //create wallet
        $create_wallet = UserService::createWallet($user[1]);
        
        //check if registration fail
        if(!$create_wallet[0]){
            return response()->json(['status' => false, 'message' => $create_wallet[1], 'data' => false], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        return response()->json(['status' => true, 'message' => 'Wallet created successfully', 'data' => ['user_record'=> $user[1], 'wallet_record'=>$create_wallet[1]]], Response::HTTP_OK);
    }
}
