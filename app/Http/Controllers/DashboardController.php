<?php

namespace App\Http\Controllers;

use App\Models\MainProperty;
use App\Models\MainPropertyGroup;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use App\Models\userProperty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function fetch_dashboard_stats()
    {
        $total_properties = MainProperty::count();
        $total_users = User::where('user_type_id', '!=', 2)->count();
        $total_groups = MainPropertyGroup::count();
        $active_properties = MainProperty::where('status', 'active')->count();
        $open_groups = MainPropertyGroup::whereRaw('no_of_people_reg < no_of_people')->count();
        $closed_groups = MainPropertyGroup::whereRaw('no_of_people = no_of_people_reg')->count();
        $property_types = PropertyType::count();

        return [
            'total_properties' => $total_properties,
            'total_users' =>  $total_users,
            'total_groups' => $total_groups,
            'active_properties' => $active_properties,
            'open_groups' => $open_groups,
            'closed_groups' => $closed_groups,
            'property_types' => $property_types
        ];
    }

    public function fetch_dashboard_chart_data()
    {
        $property['name'] = [];
        $property['values'] = [];
        $properties = Property::all();

        foreach ($properties as $key => $prop) {
            array_push($property['name'], $prop->name);

            $investors = DB::table('user_properties as up')
                            ->join('main_property_groups as mpg', 'up.main_property_group_id', 'mpg.id')
                            ->join('main_properties as mp', 'mpg.main_property_id', 'mp.id')
                            ->join('property_types as pt', 'mp.property_type_id', 'pt.id')
                            ->join('properties as p', 'p.id', 'pt.property_id')
                            ->groupBy('up.user_id')
                            ->where('p.id', $prop->id)
                            ->count();
            array_push($property['values'], $investors);
        }
        return $property;
    }

    public function fetch_dashboard_table_data()
    {
        $properties = MainProperty::all();

        foreach ($properties as $key => $property) {
            // $property->group_count = MainPropertyGroup::where('main_property_id', $property->id)->count();
            $property->investor_count = DB::table('user_properties as up')
                                                ->join('main_property_groups as mpg', 'up.main_property_group_id', 'mpg.id')
                                                ->join('main_properties as mp', 'mpg.main_property_id', 'mp.id')
                                                ->groupBy('up.user_id')
                                                ->where('mp.id', $property->id)
                                                ->count();
        }

        return $properties;
    }
}
