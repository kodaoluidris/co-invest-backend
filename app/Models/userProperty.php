<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class userProperty extends Model
{
    use HasFactory;

    protected $fillable =['user_id', 'transaction_id', 'main_property_group_id'];
}
