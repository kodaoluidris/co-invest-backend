<?php

namespace App\Http\Controllers;

use App\Models\LoggedInUser;
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
}
