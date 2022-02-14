<?php

namespace App\Http\Controllers\Properties;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseTrait;
use App\Models\PropertyType;
use Illuminate\Support\Facades\Validator;

class PropertyTypesController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = PropertyType::join('properties as p', 'p.id', 'property_types.property_id')
        ->select('property_types.*', 'p.name as p_name', 'p.description as p_desc', 'p.id as p_id')
        ->orderBy('property_types.created_at', 'desc');

        if(request()->has('not_paginated') && request()->not_paginated) {
            if(request()->has('active_only') && request()->active_only) {
                return $this->successResponse(__('propertytype.view'), $data->where('property_types.status', 'active')->orderBy('property_types.created_at', 'desc')->get());

            }
            return $this->successResponse(__('propertytype.view'), $data->get());
        }
        return $this->successResponse(__('propertytype.view'), $data->paginate(40));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'property_id' => 'required|integer',
            'description' => 'required|string'
        ]);
        if($validator->fails()){
            return $this->failureResponse(__('propertytype.invalid'), $validator->errors()->first());
        }
        $propertyType = new PropertyType;
        $propertyType->name = request()->name;
        $propertyType->property_id = request()->property_id;
        $propertyType->description = request()->description;
        
        if($propertyType->save()) {
            return $this->successResponse(__('propertytype.created'));
            
        }
        return $this->failureResponse(__('property.error'),null,500);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $propertyType = PropertyType::where('id', $id)->first();
        return $this->successResponse(__('propertytype.single'), $propertyType);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

      /**
     * Update status of the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function toggle_status($id)
    {
        $propertytype=PropertyType::where('id', $id)->update([
            'status' => request()->status
        ]);

        return $this->successResponse(__('propertytype.updated'), $propertytype); 
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'property_id' => 'required|integer',
            'description' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->failureResponse(__('propertytype.invalid'), $validator->errors()->first());
        }
        $update_property_type = PropertyType::where('id', $id)->first();
        if(!$update_property_type) return $this->failureResponse(__('propertytype.notfound'));

        $update_property_type->name = request()->name;
        $update_property_type->property_id = request()->property_id;
        $update_property_type->description = request()->description;
        if(request()->has('status')) $update_property_type->status = request()->status;
        $update_property_type->save();
        return $this->successResponse(__('propertytype.updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $delete_property_type = PropertyType::where('id', $id)->first();
        if(!$delete_property_type)return $this->failureResponse(__('propertytype.notfound'));

        try {
            if($delete_property_type->delete()) {

                return $this->successResponse(__('propertytype.delete'));
            };
            return $this->failureResponse(__('propertytype.error'),null);
        } catch (\Throwable $th) {
            return $this->failureResponse(__('propertytype.error'),null, 500);

        }
    }
}
