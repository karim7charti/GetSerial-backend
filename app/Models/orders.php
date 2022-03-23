<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class orders extends Model
{
    use HasFactory;
    protected $fillable=['id_user','id_product','quantity','livred','returned','see','buyPrice','ask_refund'];
}
