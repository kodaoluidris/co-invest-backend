<?php

namespace App\Http\Controllers\Properties;

use App\Http\Controllers\Controller;
use App\Models\MainProperty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\ApiResponseTrait;
use App\Models\MainPropertyGroup;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MainPropertyController extends Controller
{
    use ApiResponseTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function index()
    {
        $data = MainProperty::join('property_types as pt', 'pt.id', 'main_properties.property_type_id')
        ->select('main_properties.*', 'pt.name as pt_name', 'pt.description as pt_desc', 'pt.id as pt_id');
        if(request()->has('client_request')){
            $data->where('main_properties.status', 'active');
        }
        $data = $data->orderBy('main_properties.created_at','desc')->paginate(40);
        foreach ($data as  $value) {
            $value->image = json_decode($value->image);
            $value->filename = json_decode($value->filename);
            $value->more_infos = json_decode($value->more_infos);
            $group_allocated = MainPropertyGroup::where('main_property_id',$value->id)->get()
            ->makeHidden(['created_at', 'updated_at']);
            
            if(count($group_allocated) > 0) {
                $value->group_allocated = $group_allocated;

            } else {
                $value->group_allocated = false;

            }
        }
        return $this->successResponse(__('mainproperty.view'), $data);
    }

    public function add_more()
    {
        $json_data=[];
        $add_more = MainProperty::where('id', request()->main_property_id)->first();
        $old_json = json_decode($add_more->more_infos);
        foreach (request()->values as $key => $value) {
            array_push($json_data, $value);
        }
        if(!is_null($old_json) && count($old_json) > 0) {
            array_push($json_data, ...$old_json);
        }
        $add_more->more_infos = json_encode($json_data);
        if($add_more->save()) return response()->json('Action successful', 200);
        return response()->json('Action not successful', 500);
        
    }

   
    public function allocate_groups()
    {

        DB::transaction(function(){
            $validator = Validator::make(request()->all(), [
                'main_property_id' => 'required',
                'values' => 'required|array',
                'groups' => 'required'
            ]);
            if($validator->fails()){
                return $this->failureResponse(__('property.mainproperty'), $validator->errors()->first());
            }
            $url= url(request()->header('origin'));
            $data=[];
            foreach(request()->values as $key => $value) {
                array_push($data, [
                    "main_property_id" => (int) request()->main_property_id,
                    "no_of_people" => (int) $value["np"],
                    "group_name" => $value["gn"],
                    "group_price" => (int) $value["price"],
                    "groups" => (int) request()->groups
                ]);

            }
            $save = DB::table('main_property_groups')->insert($data);
            $data = MainPropertyGroup::where('main_property_id', request()->main_property_id)->select('id')->get();
            
            foreach($data as $value) {
                $update_link = MainPropertyGroup::where([
                    'main_property_id'=> request()->main_property_id,
                    'id' => $value['id']
                ])->update([
                    'url' => $url. '/home/main-property/groups/details/' . $value["id"]
                ]);
            }
        });
       

        return $this->successResponse(__('mainproperty.created'));
        
        return $this->failureResponse(__('mainproperty.error'),null,500);
    }

    public function edit_allocate_groups($id)
    {
        
        $validator = Validator::make(request()->all(), [
            'main_property_id' => 'required',
            'values' => 'required|array',
            'groups' => 'required'
        ]);
        if($validator->fails()){
            return $this->failureResponse(__('property.mainproperty'), $validator->errors()->first());
        }
        //Getting the list of groups id for update
        $main_groups_ids = array_map(function($item) {
            return $item["id"];
        },request()->values);
        

        $save = MainPropertyGroup::where([
            'main_property_id'=> $id,
            'groups' => request()->groups
        ])
        ->whereIn('id', $main_groups_ids)->upsert(
            request()->values,
            ['id','main_property_id','no_of_people', 'group_price', 'groups'],
            ['no_of_people', 'group_price']
        );

        if($save) return $this->successResponse(__('mainproperty.updated'));
        return $this->failureResponse(__('mainproperty.error'),null,500);
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
            'description' => 'required|string',
            'appreciate' => 'required',
            'appreciate_percent' => 'required',
            'location' => 'required',
        ]);

        if($validator->fails()){
            return $this->failureResponse(__('property.mainproperty'), $validator->errors()->first());
        }
        $more_infos =[];
        $mproperty = new MainProperty;
        $mproperty->name = request()->name;
        $mproperty->property_type_id = request()->property_type_id;
        $mproperty->price = request()->price;
        $mproperty->groups = request()->groups;
        $mproperty->description = request()->description;
        $more_infos[] = ["name" => "appreciate", "value" => request()->appreciate];
        $more_infos[] = ["name" => "appreciate_percent", "value" => request()->appreciate_percent];
        $more_infos[] = ["name" => "location", "value" => request()->location];
        $mproperty->more_infos = json_encode($more_infos);
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
