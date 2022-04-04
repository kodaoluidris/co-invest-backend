<?php

use App\Models\QuickSaleHistory;
use App\Models\User;
use App\Models\userProperty;
use Illuminate\Support\Facades\DB;

function validateUserById($id, $columns=null)
{
    $clmns = $columns == null ? '*' : implode(',',$columns);
    $query = User::where('id', $id)->select($clmns)->first();
    return $query ?? 'notFound';
}

function user_slot_in_property($usr_id, $prop_id) {
    
    $data1 = userProperty::where('user_id', $usr_id)->where('main_property_group_id', $prop_id)->count();

    return $data1;
}

function countNotInterested($quick_sale_id, $no_of_p)
{
    // return $no_of_p;
    $countData = QuickSaleHistory::where([
        'quick_sale_id'=> $quick_sale_id,
        'status_action'=> 'notInterested'
    ])->get();
    
    if(count($countData) == $no_of_p) return 1;
    return 0;
}