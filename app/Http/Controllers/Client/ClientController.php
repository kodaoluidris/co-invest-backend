<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Properties\MainPropertyController;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseTrait;
use App\Models\MainPropertyGroup;
use App\Models\MainProperty;

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
        $data->image = json_decode($data->image);
        return $this->successResponse(__('mainproperty.single'), $data);
        

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
