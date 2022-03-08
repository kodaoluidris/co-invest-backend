<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Properties\MainPropertyController;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseTrait;
use App\Models\MainPropertyGroup;
use App\Models\MainProperty;
use App\Models\Transaction;
use App\Models\userProperty;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $Data = new MainPropertyController;
        $data = $Data->index();
        return $this->successResponse(__('mainproperty.view'), $data);


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
        $data = MainProperty::join('property_types as pt', 'pt.id', 'main_properties.property_type_id')
        ->select('main_properties.*', 'pt.name as pt_name', 'pt.description as pt_desc', 'pt.id as pt_id')
        ->where('main_properties.id', $id)->first()->makeHidden(['created_at', 'updated_at', 'filename']);
        if($data) {
            $data->image = json_decode($data->image);
            $data->all_groups = MainPropertyGroup::where('main_property_id', $id)->get();

            foreach($data->all_groups as $value) {
                $value['group_open'] = $value['no_of_people'] != $value['no_of_people_reg'] ? true : false;
            }
        }

        return $this->successResponse(__('mainproperty.single'), $data);
        

    }

    
    public function single_group($id)
    {
        $data = MainPropertyGroup::where('main_property_groups.id', $id)
        ->join('main_properties as mp','mp.id', 'main_property_groups.main_property_id')
        ->join('property_types as pt', 'mp.property_type_id', 'pt.id')
        ->select('mp.*', 'main_property_groups.*', 'mp.id as mp_id', 'pt.name as p_name')
        ->first();
        
        
        if(!$data)  return $this->failureResponse(__('Not found'),null,404);
        $data = $data->makeHidden(['created_at', 'updated_at', 'filename']);
        if($data->no_of_people == $data->no_of_people_reg) return $this->successResponse(__('Not allowed'),$data,208);
        $data->members = userProperty::join('users', 'users.id', 'user_properties.user_id')
        ->where('user_properties.main_property_group_id', $data->id)
        ->select(
            'users.fname','users.lname','users.email','users.phone','users.username',
            DB::raw("COUNT(user_id) as total_slot"))
            ->groupBy('user_properties.user_id', 'user_properties.main_property_group_id', 'users.fname', 'users.lname', 'users.email', 'users.phone', 'users.username')->get();

        $data->image = json_decode($data->image);
        return $this->successResponse(__('mainproperty.single'), $data);
        

    }


    public function checkout()
    {
        request()->validate([
            'user_id' => 'required',
            'main_property_group_id' => 'required',
        ]);
        DB::transaction(function() {
            $insert_trans = new Transaction;
            $insert_trans->user_id = request()->user_id;
            $insert_trans->main_property_group_id = request()->main_property_group_id;
            $insert_trans->amount = request()->amount;
            $insert_trans->status = 'approved';
            $insert_trans->save();

            $userProperty = userProperty::create([
                'user_id' => request()->user_id,
                'main_property_group_id' => request()->main_property_group_id,
                'transaction_id' => $insert_trans->id
            ]);
            $incrementColumn = MainPropertyGroup::where('id', request()->main_property_group_id)->first();
            $incrementColumn->no_of_people_reg += 1;
            $incrementColumn->save();

        });
        return response('Checkout successfully', 200);
        return response('Something went wrong', 500);
    }


    /*** CLIENT Investment  ***/
    public function investment_index()
    {
        request()->validate([
            'user_id' => 'required'
        ]);
        //$data2=[];
        $data = userProperty::
        join('main_property_groups as mpg', 'mpg.id', 'user_properties.main_property_group_id')
        ->join('main_properties as mp', 'mp.id', 'mpg.main_property_id')
        ->select(
            'mp.*', 'mp.id as mp_id','mpg.id as mpg_id', 'mpg.group_name','user_properties.*',
            DB::raw("count(user_id) as total_slot")
        )
        ->groupBy('user_id', 'main_property_group_id', 'mp.id', 'mpg.id', 'user_properties.id')
        ->where('user_id', request()->user_id)->orderBy('user_properties.created_at', 'desc')->get();
        
        foreach($data as $value) {
            $value['image'] = json_decode($value['image']);
        }
        return($data);
    }

    public function single_investment($id)
    {
        request()->validate([
            'user_id' => 'required'
        ]);
        //$data2=[];
        $data = userProperty::
        join('main_property_groups as mpg', 'mpg.id', 'user_properties.main_property_group_id')
        ->join('main_properties as mp', 'mp.id', 'mpg.main_property_id')
        ->select(
            'mp.*', 'mp.id as mp_id','mpg.id as mpg_id', 'mpg.group_name','mpg.group_price',
            'mpg.no_of_people', 'mpg.no_of_people_reg', 'mpg.url'
            ,'mpg.groups','user_properties.*'
        )
        ->where([
            'user_id'=> request()->user_id,
            'user_properties.id' => $id
        ])->orderBy('user_properties.created_at', 'desc')->first();
        if(!$data) return response()->json('Not found', 404);
        $data->members = userProperty::join('users', 'users.id', 'user_properties.user_id')
        ->where('user_properties.main_property_group_id', $data->mpg_id)
        ->select(
            'users.fname','users.lname','users.email','users.phone','users.username',
            DB::raw("COUNT(user_id) as total_slot"))
            ->groupBy('user_properties.user_id', 'user_properties.main_property_group_id', 'users.fname', 'users.lname', 'users.email', 'users.phone', 'users.username')->get();
        $data->image = json_decode($data->image);
        return($data);
    }



    public function get_analytics($id)
    {
        $data = userProperty::
        join('transactions as tr','tr.id', 'user_properties.transaction_id')
        ->select(DB::raw("SUM(tr.amount) as total_paid, 
            COUNT(user_properties.id) as total_bought
        ")
        )
        ->where('user_properties.user_id', $id)->first();
        $transactions = Transaction::where('user_id', $id)->get();
        $data->transactions = $transactions;
        return $data;
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
