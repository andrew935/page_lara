<?php

namespace App\Identity;

use Illuminate\Database\Eloquent\Model;

class AccountUserRole extends Model
{
    protected $fillable = [
        'account_id',
        'user_id',
        'role',
    ];
}


