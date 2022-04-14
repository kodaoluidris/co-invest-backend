<?php

namespace App\Http\Controllers;

use App\Models\LoggedInUser;
use App\Models\MainProperty;
use App\Models\MainPropertyGroup;
use App\Models\QuickSaleHistory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\userProperty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function fetch_all_users(Request $request)
    {
        $users = User::where('user_type_id', '!=', 2);
        if($request->has('filter')) {
            if(!is_null($request->input('filter.search'))) {
                $searchData = $request->input('filter.search');
                $users->where([['lname', 'LIKE', "%{$searchData}%"], ['user_type_id', '!=', 2]])
                    ->orWhere([['fname', 'LIKE', "%{$searchData}%"], ['user_type_id', '!=', 2]])
                    ->orWhere([['mname', 'LIKE', "%{$searchData}%"], ['user_type_id', '!=', 2]])
                    ->orWhere([['email', 'LIKE', "%{$searchData}%"], ['user_type_id', '!=', 2]])
                    ->orWhere([['phone', 'LIKE', "%{$searchData}%"], ['user_type_id', '!=', 2]])
                    ->orWhere([['username', 'LIKE', "%{$searchData}%"], ['user_type_id', '!=', 2]]);
            }
        }
        return $users->get();
    }

    public function update_user_details(Request $request)
    {
        $request->validate([
            'lname' => 'required',
            'fname' => 'required',
            'gender' => 'required',
            'email' => 'required',
            'username' => 'required'
        ]);
        $user = User::where('id', $request->id)->update([
            'fname' => $request->fname,
            'lname' => $request->lname,
            'mname' => $request->mname,
            'email' => $request->email,
            'phone' => $request->phone,
            'username' => $request->username,
            'gender' => $request->gender
        ]);
        return true;
    }

    public function toggle_user_status($id)
    {
        $user = User::where('id', $id)->update([
            'status' => request()->status
        ]);

        return $user;
    }

    public function delete_user_account($id)
    {
        DB::transaction(function() use ($id) {
            LoggedInUser::where('user_id', $id)->delete();
            QuickSaleHistory::where('user_id', $id)->delete();
            Transaction::where('user_id', $id)->delete();
            userProperty::where('user_id', $id)->delete();
            User::where('id', $id)->delete();
        });
        return true;
    }

    public function reset_user_password($id)
    {
        User::where('id', $id)->update([
            'password' => Hash::make('12345678')
        ]);
        return true;
    }

    public function user_investments(Request $request)
    {
        $user_properties =  MainProperty::join('main_property_groups as mpg', 'mpg.main_property_id', 'main_properties.id')
                                ->join('user_properties as up', 'up.main_property_group_id', 'mpg.id')
                                ->select('main_properties.*')
                                ->where('up.user_id',$request->user_id)
                                ->groupBy('main_properties.id')
                                ->get();
        foreach ($user_properties as $key => $property) {
            $property_details = MainPropertyGroup::join('user_properties as up', 'up.main_property_group_id', 'main_property_groups.id')
                                    ->select('main_property_groups.id as mpg_id', 'main_property_groups.group_name', 'up.*',
                                            'main_property_groups.status as mpg_status',  DB::raw("count(up.user_id) as total_slot"))
                                    ->where(['main_property_groups.main_property_id' => $property->id])
                                    ->orderBy('up.created_at', 'desc')->first();
            $property->image = json_decode($property->image);
            $property->details = $property_details;
        }
        return $user_properties;
    }
}
