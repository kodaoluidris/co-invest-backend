<?php

namespace App\Http\Controllers\Properties;

use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\Controller;
use App\Models\MainProperty;
use App\Models\MainPropertyGroup;
use App\Models\NotIntrestedNotification;
use App\Models\QuickSale;
use App\Models\QuickSaleHistory;
use App\Models\QuickSoledProperty;
use App\Models\Transaction;
use App\Models\userProperty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserGroupsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $allPropertyGroups = MainProperty::orderBy('created_at', 'desc')->paginate(30);

        foreach ($allPropertyGroups as $key => $value) {
            $value->image = json_decode($value->image);
           $value["all_groups"] = MainPropertyGroup::where('main_property_id', $value['id'])->get();
           foreach ($value["all_groups"] as $key => $value2) {
               $value2["users"] =MainPropertyGroup::join("user_properties as up", "up.main_property_group_id", "main_property_groups.id")
               ->join('users', 'users.id', 'up.user_id')
               ->where('up.main_property_group_id', $value2['id'])
               ->select(
               'users.fname', 'users.lname', 'users.email', 'users.country','users.gender','up.main_property_group_id',
               'up.user_id', 'up.transaction_id', 'up.created_at',DB::raw("COUNT(up.id) as total_slot")
               )->orderBy('up.created_at', 'desc')->groupBy('up.user_id', 'up.main_property_group_id')
               ->get();
           }
           
        }
        return response($allPropertyGroups, 200);
    }

    public function add_user()
    {
        request()->validate([
            'main_property_group_id' => 'required',
            'user_id' => 'required',
            'slot' => 'required'
        ]);
        DB::transaction(function() {
            $clientController = new ClientController;
            $transaction = $clientController->checkout();
            $value= $transaction->getData()->data;
            
            for ($i=0; $i < request()->slot; $i++) { 
                $userProperty = userProperty::create([
                    'user_id' => request()->user_id,
                    'transaction_id' => $value->data->id,
                    'main_property_group_id' => request()->main_property_group_id
                ]);
            }
          
            $transactionStatus = Transaction::where('id', $value->data->id)->update([
                'status' => 'approved'
            ]);

            $populateTotalReg = MainPropertyGroup::where('id', request()->main_property_group_id)->first();
            $populateTotalReg->no_of_people_reg += request()->slot;
            
          
        });

        return response('Action successful', 200);
      
    }

    public function change_status()
    {
        return MainPropertyGroup::where('id', request()->id)->update([
            'status' => request()->status
        ]) >0 ? response('Action Successful', 200) : response('Something went wrong', 500);
    }

    public function remove_user()
    {
        request()->validate([
            'mpg_id' => 'required',
            'user_id' => 'required',
            'slot' => 'required'
        ]);
        DB::transaction(function() {
            $userProperty = userProperty::where(['user_id' => request()->user_id, 'main_property_group_id' => request()->mpg_id])
            ->select('id')->first();
            if(!blank($userProperty)) {
                $quickSale =QuickSale::where('user_property_id', $userProperty->id)->select('id')->first();
                if(!blank($quickSale)) {
                    $quickHistory = QuickSaleHistory::where('quick_sale_id', $quickSale->id)->delete();
                    $notInterested = NotIntrestedNotification::where('quick_sale_id', $quickSale->id)->delete();
                    $quickSale->delete();
                }
                $userProperty->delete();
                
                // Remove Slot count 
                $mainPG = MainPropertyGroup::where('id', request()->mpg_id)->first();
                $mainPG->no_of_people_reg -= request()->slot;
                $mainPG->save();
                $transaction = Transaction::where(['user_id' => request()->user_id, 'main_property_group_id' => request()->mpg_id])->update([
                    'status' => 'removed'
                ]);
            }
        });

        return response('Action successful', 200);
      
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
