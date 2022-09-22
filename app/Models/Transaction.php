<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_user_id',
        'beneficiary_user_id',
        'reference_no',
        'transaction_id',
        'narration',
        'other',
        'status',
    ];
}
