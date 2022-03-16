<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuickSaleHistory extends Model
{
    use HasFactory;
    protected $fillable=['quick_sale_id', 'approved_users', 'declined_users'];

}
