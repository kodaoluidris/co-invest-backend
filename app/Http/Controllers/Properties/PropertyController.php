<?php

namespace App\Http\Controllers\Properties;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PropertyController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $all_property = Property::select('*')->paginate(40);
        return $this->successResponse(__('property.view'), $all_property);

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
            'description' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->failureResponse(__('property.invalid'), $validator->errors()->first());
        }

        $property = new Property;
        $property->name = request()->name;
        $property->description = request()->description;
        
        if($property->save()) {
            $images = [];
            $filenames = [];
            foreach (request()->file('image') as  $value) {
                $image = time().'_'.$value->getClientOriginalName();
                $path = $value->storeAs('public/images', $image);
 
                $path = url('/') . '/storage/images/' . $image;
 
                $images[] = ['image' => $path];
                $filenames[] = ['filename' => $image];
 
 
            };

            $add_property_img = Property::where('id', $property->id)->update([
                'image' => json_encode($images),
                'filename' => json_encode($filenames)
            ]);

            if($add_property_img) {
                return $this->successResponse(__('property.created'));
                
            }
            return $this->failureResponse(__('property.error'),null,500);

        }


    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $property = Property::where('id', $id)->first();
        return $this->successResponse(__('property.single'), $property);

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
            'description' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->failureResponse(__('property.invalid'), $validator->errors()->first());
        }
        $update_property = Property::where('id', $id)->first();
        if(!$update_property) return $this->failureResponse(__('property.notfound'));

        $update_property->name = request()->name;
        $update_property->description = request()->description;
        if(request()->has('status')) $update_property->status = request()->status;
        $update_property->save();
        return $this->successResponse(__('property.updated'));

        

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $delete_property = Property::where('id', $id)->first();
        if(!$delete_property)return $this->failureResponse(__('property.notfound'));

        try {
        
            foreach (json_decode($delete_property->filename) as $value) {
                # code...
                if (Storage::exists("app/public/images/".$value->filename)) {
                    # code...
                    unlink(storage_path("app/public/images/".$value->filename));
                }
            }
            $delete_property->delete();
            return $this->successResponse(__('property.delete'));

        } catch (\Throwable $th) {
            return $this->failureResponse(__('property.error'),null, 500);

        }


    }
}
