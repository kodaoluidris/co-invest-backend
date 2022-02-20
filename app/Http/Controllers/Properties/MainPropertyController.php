<?php

namespace App\Http\Controllers\Properties;

use App\Http\Controllers\Controller;
use App\Models\MainProperty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Storage;

class MainPropertyController extends Controller
{
    use ApiResponseTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = MainProperty::join('property_types as pt', 'pt.id', 'main_properties.property_type_id')
        ->select('main_properties.*', 'pt.name as pt_name', 'pt.description as pt_desc', 'pt.id as pt_id')
        ->orderBy('main_properties.created_at','desc')
        ->paginate(40);
        foreach ($data as  $value) {
            $value->image = json_decode($value->image);
            $value->filename = json_decode($value->filename);
        }
        return $this->successResponse(__('mainproperty.view'), $data);
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
            'image' => 'required',
            'property_type_id' => 'required|integer',
            'price' => 'required|integer',
            'groups' => 'required|integer',
            'description' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->failureResponse(__('property.mainproperty'), $validator->errors()->first());
        }
        $mproperty = new MainProperty;
        $mproperty->name = request()->name;
        $mproperty->property_type_id = request()->property_type_id;
        $mproperty->price = request()->price;
        $mproperty->groups = request()->groups;
        $mproperty->description = request()->description;
        if($mproperty->save()) {
            $images = [];
            $filenames = [];
            foreach (request()->file('image') as  $value) {
                $image = time().'_'.$value->getClientOriginalName();
                $path = $value->storeAs('public/images', $image);
    
                $path = url('/') . '/storage/images/' . $image;
    
                $images[] = ['image' => $path];
                $filenames[] = ['filename' => $image];
    
    
            };
    
            $add_mproperty_img = MainProperty::where('id', $mproperty->id)->update([
                'image' => json_encode($images),
                'filename' => json_encode($filenames)
            ]);
    
            if($add_mproperty_img) {
                return $this->successResponse(__('mainproperty.created'));
                
            }
            return $this->failureResponse(__('mainproperty.error'),null,500);
        }
        return $this->failureResponse(__('mainproperty.error'),null,500);

      
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        $mainProperty=MainProperty::where('id', $id)->update([
            'status' => request()->status
        ]);

        return $this->successResponse(__('mainproperty.updated'), $mainProperty); 
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
            'price' => 'required|integer',
            'groups' => 'required|integer',
            'property_type_id' => 'required|integer',
            'description' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->failureResponse(__('mainproperty.invalid'), $validator->errors()->first());
        }
        $update_main_property = MainProperty::where('id', $id)->first();
        if(!$update_main_property) return $this->failureResponse(__('mainproperty.notfound'));

        $update_main_property->name = request()->name;
        $update_main_property->property_type_id = request()->property_type_id;
        $update_main_property->description = request()->description;
        $update_main_property->price = request()->price;
        $update_main_property->groups = request()->groups;
        if(request()->has('status')) $update_main_property->status = request()->status;
        $update_main_property->save();
        return $this->successResponse(__('mainproperty.updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $delete_main_property = MainProperty::where('id', $id)->first();
        if(!$delete_main_property)return $this->failureResponse(__('mainproperty.notfound'));
        $filenames_decoded = json_decode($delete_main_property->filename);
        try {
            foreach ($filenames_decoded as $value) {
                # code...
                if (Storage::exists("app/public/images/".$value->filename)) {
                    # code...
                    unlink(storage_path("app/public/images/".$value->filename));
                }
            }
            if($delete_main_property->delete()) {

                return $this->successResponse(__('mainproperty.delete'));
            };
            return $this->failureResponse(__('mainproperty.error'),null);
        } catch (\Throwable $th) {
            return $this->failureResponse(__('mainproperty.error'),null, 500);

        }
    }
}
