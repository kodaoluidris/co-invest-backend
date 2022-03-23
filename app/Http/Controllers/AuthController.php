<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\LoggedInUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|confirmed|string|min:8',
        ]);
            if ($validator->fails()) {
              return response()->json($validator->errors(),405);
            }
            $user = new User;
            $user->fname = $request->fname;
            $user->lname = $request->lname;
            $user->email = $request->email;
            $user->user_type_id = 3;
            $user->password = Hash::make($request->password);
            $user->save();
         
            $credentials = request(['email', 'password']);

            if (!$token = auth()->attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return $this->respondWithToken($token);
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

       
        // $logged = LoggedInUser::where('user_id',$token)
        //     ->where(DB::raw('substr(created_at, 1, 10)'), '=' , Carbon::now()->format('Y-m-d'))->count();
               
                // if($logged > 0){
    
                //     $logged = LoggedInUser::where('user_id',$token)
                //         ->where(DB::raw('substr(created_at, 1, 10)'), '=' , Carbon::now()->format('Y-m-d'))->first();
                        
                //     $time_arr = json_decode($logged->logged_time, true);
                //     $time_arr[] =  Carbon::now();
                    
                //     $logged->update([
                //         'logged_time' => json_encode($time_arr)
                //     ]);
    
                // }else{
                // LoggedInUser::create([
                //     'user_id'=>$token,
                //     'logged_time'=>json_encode([Carbon::now()])
                //     ]);
                // }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function auth_user_type()
    {
       $user = User::join('user_types', 'user_types.id', 'users.user_type_id')
       ->where('users.id', request()->id)->selectRaw('user_types.name')->first();
       if(!$user) return response()->json('Invalid user', 405);
       return response()->json($user,200);
    }

    public function complete_profile() 
    {

        $update_user = User::where('id', request()->id)->update([
            'fname' => request()->fname,
            'lname' => request()->lname,
            'mname' => request()->mname,
            'gender' => request()->gender,
            'username' => request()->username,
            'email' => request()->email,
            'phone' => request()->phone,
            'country' => request()->country
        ]);

        if($update_user) return response()->json(['message' => 'Profile updated successfully'], 200);
        return response()->json(['message' => 'Unable tp update profile, please try again later'], 500);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'data' => auth()->user()
        ]);
    }
}