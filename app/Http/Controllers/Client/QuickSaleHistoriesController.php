<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\QuickSale;
use App\Models\QuickSaleHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuickSaleHistoriesController extends Controller
{
    public function sale_notification()
    {
        request()->validate([
            'id' => 'required'
        ]);
       
        $data = QuickSale::join('user_properties as up', 'up.id', 'quick_sales.user_property_id')
        ->join('users', 'users.id', 'up.user_id')
        ->where('up.user_id', '!=', request()->id)
        ->whereNotIn('quick_sales.id', [DB::raw("select quick_sale_id from quick_sale_histories")])
        ->select('quick_sales.description','users.fname','users.gender', 'users.lname', 'quick_sales.id')
        ->get();
        
        

        return $data;

    }

    public function reply_sale_notification()
    {
        DB::transaction(function() {
            $create_transaction = new QuickSaleHistory;
            $create_transaction->user_id = request()->userId;
            $create_transaction->quick_sale_id = request()->id;
            $create_transaction->status_action = request()->msg;
            $create_transaction->save();

            $quick_sale_status = QuickSale::where('id', request()->id)->first();
            if($quick_sale_status->status == 'pending') $quick_sale_status->status = 'processing';
            $quick_sale_status->save();

        });

        return response()->json('Action successful', 200);
    }
}
