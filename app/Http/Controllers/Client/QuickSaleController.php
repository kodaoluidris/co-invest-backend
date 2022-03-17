<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\QuickSale;
use App\Models\QuickSaleHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuickSaleController extends Controller
{
    public function sell_portion()
    {
        request()->validate([
            'user_property_id' => 'required',
            'amount' => 'required',
            'description' => 'required',
        ]);
        $check_pending_sale = QuickSale::where('user_property_id', request()->user_property_id)->count();
        if($check_pending_sale > 0) return response('Action not successful', 405);
        DB::transaction(function() {
            $quickSale = QuickSale::create([
                'user_property_id' => request()->user_property_id,
                'amount' => request()->amount,
                'description' =>  request()->description
            ]);
            
        });

        return response('Action successful', 200);

    }

  
}
