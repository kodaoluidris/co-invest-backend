<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\NotIntrestedNotification;
use App\Models\QuickSale;
use App\Models\QuickSaleHistory;
use App\Models\userProperty;
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
       $getUserPropertyId = userProperty::where('user_properties.user_id', $auth_id)
       ->join('main_property_groups as mpg', 'mpg.id', 'user_properties.main_property_group_id')->select('mpg.id')->get()->toArray();

       $g_members = userProperty::where('user_properties.user_id','!=', $auth_id)
       ->join('users', 'users.id', 'user_properties.user_id')
       ->join('main_property_groups as mpg', 'mpg.id', 'user_properties.main_property_group_id')
       ->select('mpg.id')
       ->whereIn('mpg.id', array_column($getUserPropertyId, 'id'))
       ->get()->toArray();
    //    return $getUserPropertyId;
        $data = QuickSale::join('user_properties as up', 'up.id', 'quick_sales.user_property_id')
        ->join('main_property_groups as mpg', 'mpg.id', 'up.main_property_group_id')
        ->join('users', 'users.id', 'up.user_id')
        ->where('up.user_id', '!=', $user->id)
        ->where('mpg.status', 'active')
        ->whereIn('mpg.id', array_column($getUserPropertyId, 'id'))
        ->whereIn('up.main_property_group_id', array_column($g_members, 'id'))
        ->whereNotIn('quick_sales.id', 
            [
                DB::raw("
                    select quick_sale_id from quick_sale_histories where user_id=$auth_id
                ")
            ]
        )->whereNotIn('quick_sales.id', [
            DB::raw("
            select quick_sale_id from not_intrested_notifications
           ")
        ])
        ->select('quick_sales.description','users.fname','users.gender', 'users.lname', 'quick_sales.id', 'mpg.id as mpg_id')
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
        $getUserPropertyId = userProperty::where('user_id', $user->id)
        ->join('main_property_groups as mpg', 'mpg.id', 'user_properties.main_property_group_id')->select('mpg.id')->distinct()->get()->toArray();
        $mpg_ids = array_column($getUserPropertyId, 'id');
        // return $mpg_ids;
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
        ->where('quick_sales.status', '!=', 'closed')
        ->whereIn('mpg.id', $mpg_ids)
        
        ->select(
            'quick_sales.description','users.fname','quick_sales.amount',
            'mp.name', 'mpg.group_name','mpg.id as mpg_id','mpg.status as mpg_status',
            'users.gender', 'users.lname',
            'quick_sales.id','qsh.status_action',
        )->distinct()
        ->orderBy('quick_sales.created_at', 'desc')
        ->get();

        return response()->json($data,200);
    }

    public function my_quick_sales()
    {
        request()->validate([
            'id' => 'required'
        ]);
        $user =  validateUserById(request()->id,['id']);
        if($user == 'notFound') return response('User with the given Id is Invalid',405);
        // QUick sales history Logic
        
        $quick_sales_transactions = QuickSale::join('user_properties as up', 'up.id', 'quick_sales.user_property_id')
            ->join('main_property_groups as mpg', 'mpg.id', 'up.main_property_group_id')
            ->join('main_properties as mp', 'mp.id', 'mpg.main_property_id')
            ->selectRaw('
                up.user_id as owner_id,mp.name as mp_name, mpg.group_name,mpg.status as mpg_status,
                quick_sales.id, quick_sales.status,
                quick_sales.description, quick_sales.amount
            ')
            ->where('up.user_id', $user->id)->where('quick_sales.status', '!=', 'closed')
            ->orderBy('quick_sales.created_at', 'desc')
        ->get();
        
        foreach ($quick_sales_transactions as $quick_sale) {
            // Check if it has populated record to not interested table, so as to notify user.
            $determineIfRecordExistInNotIntrestedTable = NotIntrestedNotification::where([
                'user_id' => $user->id,
                'quick_sale_id' => $quick_sale['id']
            ])->first();

            if(!blank($determineIfRecordExistInNotIntrestedTable)) {
                $quick_sale['no_interest'] = true;
            } else {
                $quick_sale['no_interest'] = false;

            }
            
            
           
           $quick_sale->interactors = QuickSaleHistory::join('users', 'users.id', 'quick_sale_histories.user_id')
            ->selectRaw(
                'quick_sale_histories.*,users.fname as buyer_fname,
                users.lname as buyer_lname,users.email as buyer_email' 
            )->where('quick_sale_histories.quick_sale_id', $quick_sale['id'])
            ->orderBy('quick_sale_histories.created_at', 'desc')
            ->groupBy('quick_sale_histories.quick_sale_id', 'quick_sale_histories.user_id')
            ->get();
        }
        
        return response()->json($quick_sales_transactions,200);

    }

    public function final_quick_sale()
    {
        request()->validate([
            'quick_sale_id' => 'required',
            'user_id' => 'required',
            'quick_sale_history_id' => 'required',
            'buyer_id' => 'required',
        ]);

        DB::transaction(function() {
            $updateQuickSaleHistory = QuickSaleHistory::where([
                'id' => request()->quick_sale_history_id,
                'quick_sale_id' => request()->quick_sale_id,
                'status_action' => request()->status_action,
                'user_id' => request()->buyer_id,
            ])->update([
                'soled_to_me' => 'yes'
            ]);

            $quick_sale = QuickSale::where([
                'quick_sales.id' => request()->quick_sale_id,
                'up.user_id' => request()->user_id,
            ])
            ->join('user_properties as up', 'quick_sales.user_property_id', 'up.id')
            ->join('transactions as t', 't.id', 'up.transaction_id')
            ->update([
                'quick_sales.status' => 'closed',
                'up.user_id' =>  request()->buyer_id,
                't.user_id' =>  request()->buyer_id,
            ]);
        });

        return response('Action successful', 200);
    }

    public function reply_sale_notification()
    {
        $user =  validateUserById(request()->userId,['id']);

        if($user == 'notFound') return response('User with the given Id is Invalid',405);
         

        DB::transaction(function() use($user) {
            $getuser_slot = user_slot_in_property($user->id, request()->mpg);
            for ($i=0; $i < $getuser_slot; $i++) { 
                $create_transaction = new QuickSaleHistory;
                $create_transaction->user_id = $user->id;
                $create_transaction->quick_sale_id = request()->id;
                $create_transaction->status_action = request()->msg;
                $create_transaction->save();
    
               
            }
            $quick_sale_status = QuickSale::where(['id' => request()->id, 'status' => 'pending'])->update([
                'status' => 'processing'
            ]);

            $get_no_of_people = QuickSale::where('quick_sales.id', request()->id)
            ->join('user_properties as up', 'up.id', 'quick_sales.user_property_id')
            ->join('users', 'users.id', 'up.user_id')
            ->join('main_property_groups as mpg', 'mpg.id', 'up.main_property_group_id')
            ->select('mpg.no_of_people_reg', 'up.id', 'mpg.id as mpg_id','users.id as user_id')->first();
            // $get_members_slot = userProperty::where('main_property_group_id', $get_no_of_people->mpg_id)
            //                     ->select(DB::raw("COUNT(user_id) as total_slot"))
            //                     ->groupBy('user_id', 'main_property_group_id')->get();
            
            $user_total_slot = user_slot_in_property($get_no_of_people->user_id, $get_no_of_people->mpg_id);
            // $count = $user_total_slot;
            
            // foreach ($get_members_slot as $value) {
            //    if($value['total_slot'] > 1) {
            //        $count += $value['total_slot'] -1;
            //    }
            // }
            $determineToInsertToNotInterestedTable = countNotInterested(request()->id, $get_no_of_people->no_of_people_reg - $user_total_slot);
            
            if($determineToInsertToNotInterestedTable ==1)  {
                // Insert record
                
                $notInterestedNotification = NotIntrestedNotification::create([
                    'user_id' =>  $get_no_of_people->user_id,
                    'quick_sale_id' => request()->id
                ]);
            }

        });

        return response()->json('Action successful', 200);
    }
}
