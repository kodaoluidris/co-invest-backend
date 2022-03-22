<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $with = ['MainPropertyGroup'];

    public function MainPropertyGroup(){
        return $this->belongsTo(MainPropertyGroup::class,'main_property_group_id', 'id');
    }
}
