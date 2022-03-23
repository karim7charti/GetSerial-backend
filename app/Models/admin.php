<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class admin extends  Model implements AuthenticatableContract, CanResetPasswordContract
{
    use HasFactory,HasApiTokens ,Authenticatable, CanResetPassword;
    protected $fillable = [
        'name',
        'email',

        'password',
        'number',
    ];
}
