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
        $user =  validateUserById(request()->id,['id']);
        if($user == 'notFound') return response('User with the given Id is Invalid',405);
       $auth_id = $user->id;
        $data = QuickSale::join('user_properties as up', 'up.id', 'quick_sales.user_property_id')
        ->join('users', 'users.id', 'up.user_id')
        ->where('up.user_id', '!=', $user->id)
        ->whereNotIn('quick_sales.id', 
            [
                DB::raw("
                    select quick_sale_id from quick_sale_histories where user_id=$auth_id
                ")
            ]
        )
        ->select('quick_sales.description','users.fname','users.gender', 'users.lname', 'quick_sales.id')
        ->get();
        
        

        return $data;

    }

    public function market_place() 
    {
        request()->validate([
            'id' => 'required'
        ]);
        $user =  validateUserById(request()->id,['id']);
        if($user == 'notFound') return response('User with the given Id is Invalid',405);
        // Quick sales and histories query
        $data = QuickSale::leftJoin('quick_sale_histories as qsh', function($ljoin) use($user) {
            $ljoin->on('qsh.quick_sale_id', 'quick_sales.id');
            $ljoin->where('qsh.user_id', $user->id);
        })
        ->join('user_properties as up', 'up.id', 'quick_sales.user_property_id')
        ->join('main_property_groups as mpg', 'mpg.id', 'up.main_property_group_id')
        ->join('main_properties as mp', 'mp.id', 'mpg.main_property_id')
        ->join('users', 'users.id', 'up.user_id')
        ->where('up.user_id', '!=', $user->id)
        ->select(
            'quick_sales.description','users.fname','quick_sales.amount',
            'mp.name', 'mpg.group_name',
            'users.gender', 'users.lname',
            'quick_sales.id','qsh.status_action'
        )
        ->get();

        // QUick sales history query
        $histories = QuickSaleHistory::join('quick_sales as qs', 'qs.id', 'quick_sale_histories.quick_sale_id')
                    ->join('users', 'users.id', 'quick_sale_histories.user_id')
                    ->join('user_properties as up', 'up.id', 'qs.user_property_id')
                    ->selectRaw(
                        'up.id as up_id,quick_sale_histories.*, 
                        qs.status, qs.description,qs.amount,users.fname as buyer_fname,
                        users.lname as buyer_lname,users.email as buyer_email' 
                    )->where('up.user_id', $user->id)->orderBy('quick_sale_histories.created_at', 'desc')
                    ->get();
                    
        return response()->json([$data, $histories],200);
    }

    public function reply_sale_notification()
    {
        $user =  validateUserById(request()->userId,['id']);
        if($user == 'notFound') return response('User with the given Id is Invalid',405);
        DB::transaction(function() use($user) {
            $create_transaction = new QuickSaleHistory;
            $create_transaction->user_id = $user->id;
            $create_transaction->quick_sale_id = request()->id;
            $create_transaction->status_action = request()->msg;
            $create_transaction->save();

            $quick_sale_status = QuickSale::where(['id' => request()->id, 'status' => 'pending'])->update([
                'status' => 'processing'
            ]);

        });

        return response()->json('Action successful', 200);
    }
}
