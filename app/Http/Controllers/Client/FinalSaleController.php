<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\FinalSale;
use App\Models\MainProperty;
use App\Models\MainPropertyGroup;
use App\Models\userProperty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinalSaleController extends Controller
{
    public function initialize_property_sale(Request $request)
    {
        $accepts = [[
            'user_id' => $request->user_id,
            'status' => 'initiated'
        ]];
        $property_sale = FinalSale::firstOrNew([
            'user_id' => $request->user_id,
            'main_property_group_id' => $request->mpg_id
        ]);
        $property_sale->accepts_user_id = json_encode($accepts);
        $property_sale->total_accepts = 1;
        $property_sale->save();

        return true;
    }
    public function approve_property_sale(Request $request)
    {
        $final_sale_record = FinalSale::where('id', $request->final_sale_id)->first();
        $main_property_group = MainPropertyGroup::where('id', $final_sale_record->main_property_group_id)->first();
        $final_sale_accept = json_decode($final_sale_record->accepts_user_id);
        array_push($final_sale_accept, [
            'user_id' => $request->user_id,
            'status' => 'approved'
        ]);
        $final_sale_record->total_accepts += 1;
        $final_sale_record->accepts_user_id = json_encode($final_sale_accept);
        $final_sale_record->status = $main_property_group->no_of_people_reg == ($final_sale_record->total_accepts + 1) ? 'close' : 'open';
        $final_sale_record->save();

        return true;
    }

    public function fetch_all_property_for_sale()
    {
        $all_properties = MainProperty::join('main_property_groups as mpg', 'mpg.main_property_id', 'main_properties.id')
                                    ->join('final_sales as fs', 'fs.main_property_group_id', 'mpg.id')
                                    ->select('mpg.id as mpg_id', 'mpg.group_name', 'main_properties.*',
                                    'mpg.status as mpg_status')
                                    ->where('fs.status','open')
                                    ->get();

        foreach ($all_properties as $key => $property) {
            $user_property = DB::table('user_properties as up')
                                ->select('up.*',  DB::raw("count(up.user_id) as total_slot"))
                                ->where(['main_property_group_id' => $property->mpg_id])
                                ->orderBy('up.created_at', 'desc')->first();
            $property->image = json_decode($property->image);
            $property->details = $user_property;
        }

        // $all_properties = MainProperty::all();
        // foreach ($all_properties as $key => $property) {
        //     $user_property = MainPropertyGroup::join('user_properties as up', 'up.main_property_group_id', 'main_property_groups.id')
        //                             ->join('final_sales as fs', 'fs.main_property_group_id', 'main_property_groups.id')
        //                             ->select('main_property_groups.id as mpg_id', 'main_property_groups.group_name',
        //                                     'up.*',  DB::raw("count(up.user_id) as total_slot"))
        //                             ->where(['main_property_groups.main_property_id' => $property->id, 'fs.status' =>'open'])
        //                             ->orderBy('up.created_at', 'desc')->get();
        //     $property->image = json_decode($property->image);
        //     $property->details = $user_property;
        // }
        return($all_properties);
    }

    public function admin_buy_property_for_sale(Request $request)
    {
        $property_group = MainPropertyGroup::where('id', $request->id)->update([
            'status' => 'inactive'
        ]);
        return true;
    }
}
