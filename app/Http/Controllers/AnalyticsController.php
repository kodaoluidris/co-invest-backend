<?php

namespace App\Http\Controllers;

use App\Models\MainProperty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Property;

use function PHPSTORM_META\map;

class AnalyticsController extends Controller
{
    use ApiResponseTrait;

    public function getPropertyCount()
    {
        $main = Property::leftJoin('property_types as pt', 'pt.property_id', 'properties.id')
                ->leftJoin('main_properties as mp', 'mp.property_type_id', 'pt.id')
                ->select(DB::raw("COUNT(mp.id) as property_count, 
                                properties.name as property_name "))
                    ->groupBy(DB::raw('properties.id'))->get()->toArray();
        $mappedProperty = array_map(function ($data){
                        return $data['property_count'];
                    }, $main);
            $data = [
                "analytics" => $main,
                "total_property" => array_sum($mappedProperty)
            ];
                       
        return $this->successResponse(__('analytics.all_properties'), $data);
    }
}
