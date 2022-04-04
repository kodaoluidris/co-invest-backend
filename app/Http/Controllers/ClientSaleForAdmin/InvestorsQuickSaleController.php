<?php

namespace App\Http\Controllers\ClientSaleForAdmin;

use App\Http\Controllers\Controller;
use App\Models\MainPropertyGroup;
use App\Models\NotIntrestedNotification;
use App\Models\QuickSale;
use App\Models\QuickSaleHistory;
use App\Models\QuickSoledProperty;
use App\Models\Transaction;
use App\Models\userProperty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestorsQuickSaleController extends Controller
{
    public function index()
    {
        $data = NotIntrestedNotification::join('users', 'users.id', 'not_intrested_notifications.user_id')
                ->join('quick_sales as qs', 'qs.id', 'not_intrested_notifications.quick_sale_id')
                ->join('user_properties as up', 'up.id', 'qs.user_property_id')
                ->join('main_property_groups as mpg', 'mpg.id', 'up.main_property_group_id')
                ->join('main_properties as mp', 'mp.id', 'mpg.main_property_id')
                ->join('property_types as pt', 'pt.id', 'mp.property_type_id')
                ->selectRaw('
                    users.fname, users.lname, users.email,users.id as user_id,mpg.id as mpg_id,
                    mp.name, pt.name as pt_name,mp.price, qs.amount as paid_price,up.id as user_property_id,
                    mpg.groups,qs.status as sale_status, not_intrested_notifications.status as actual_status
                ')->orderBy('not_intrested_notifications.created_at', 'desc')->paginate(40);

        return response($data,200);
    }

    public function buy()
    {
        request()->validate([
            'user_id' => 'required',
            'mpg_id' => 'required',
            'buyer_id' => 'required',
            'user_property_id' => 'required',
            'amount' => 'required',
        ]);
        // usr property, change status transaction,
        // usr property, quick sale , quick sale quick_sale_histories delete *,
        // usr property delete *,
        // main group, decrement no_reg,
        // insert to soled history,user_id, mgp_id, buyer_id

        DB::transaction(function() {
            $user_property = userProperty::where([
                'id' => request()->user_property_id,
                'user_id' => request()->user_id
            ])->first();
            // Update transation
            $transaction = Transaction::where([
                'id'=> $user_property->transaction_id,
                'user_id'=> request()->user_id,
            ])->update([
                'status' => 'soled'
            ]);

            // Find quick sales
            $quick_sales = QuickSale::where('user_property_id', request()->user_property_id)->first();
            // Use id to delete any transaction on it
            $quick_sale_histories = QuickSaleHistory::where([
                'quick_sale_id' => $quick_sales->id,
                'user_id' => request()->user_id
            ])->delete();
            $admin_notification =   NotIntrestedNotification::where([
                'quick_sale_id' => $quick_sales->id,
                'user_id' => request()->user_id,
            ])->delete();
            $quick_sales->delete();
            $user_property->delete();

            // Decrement Column no_reg
            $main_group = MainPropertyGroup::where('id', request()->mpg_id)->first();
            $main_group->no_of_people_reg -= 1;
            $main_group->save();

            // Create History
            $history = QuickSoledProperty::create([
                'user_id' => request()->user_id,
                'main_property_group_id' => request()->mpg_id,
                'amount' => request()->amount,
                'sold_to' => request()->buyer_id,
            ]);
        });

        return response('Action successful', 200);
    }
}
