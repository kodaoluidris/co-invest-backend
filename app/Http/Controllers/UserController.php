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
}
