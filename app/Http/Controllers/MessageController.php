<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    
    public function index()
    {
        //
    }

   
    public function fetchMessages()
    {
        return Message::with('user')->where('main_property_group_id', request()->main_property_group_id)->get();
    }

  
 
    public function sendMessage(Request $request)
    {
        $user = User::where('id', request()->id)->first();

        $message = Message::create([
          'user_id' => $user->id,
          'message' => $request->input('message'),
          'main_property_group_id' => request()->main_property_group_id,
        ]);

        event(new MessageSent($user, $message));
      
        return ['status' => 'Message Sent!'];
    }

}
