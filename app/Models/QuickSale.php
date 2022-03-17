<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuickSale extends Model
{
    use HasFactory;

    protected $fillable=['user_property_id', 'amount', 'description'];
}
