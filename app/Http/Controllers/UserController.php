<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function fetch_all_users()
    {
        $users = User::where('user_type_id', '!=', 2)->get();
        return $users;
    }

    public function update_user_details(Request $request)
    {
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
}
