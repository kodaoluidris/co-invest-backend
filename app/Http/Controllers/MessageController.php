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
        $user = User::where('id', Auth::id())->first();

        $message = $user->messages()->create([
          'message' => $request->input('message'),
          'main_property_group_id' => request()->main_property_group_id,
        ]);

        broadcast(new MessageSent($user, $message))->toOthers();
      
        return ['status' => 'Message Sent!'];
    }

}
