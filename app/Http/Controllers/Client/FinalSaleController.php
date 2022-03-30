<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\FinalSale;
use App\Models\MainPropertyGroup;
use Illuminate\Http\Request;

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
}
