<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified',
        'email_verified_at',
        'password',
        'pin',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
        "pin"
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = [
        'walletcode', 'balance'
    ];

    public function walletcode(){
        return $this->hasOne(Wallet::class);
    }

    public function balance(){
        return $this->hasOne(Wallet::class);
    }

    public function getWalletcodeAttribute(){
        return $this->walletcode()->first() ? $this->walletcode()->first()->code : null;
     }

     public function getBalanceAttribute(){
        return $this->balance()->first() ? $this->balance()->first()->balance : null;
     }
}
