<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Region;
use Illuminate\Http\JsonResponse;

/**
 * Каскад танловлар учун ҳудуд маълумотларини JSON қайтаради (ochiq).
 */
class GeoController extends Controller
{
    public function districts(Region $region): JsonResponse
    {
        return response()->json(
            $region->districts()->orderBy('name')->get(['id', 'name'])
        );
    }

    public function mahallas(District $district): JsonResponse
    {
        return response()->json(
            $district->mahallas()->orderBy('name')->get(['id', 'name'])
        );
    }

    public function streets(District $district): JsonResponse
    {
        return response()->json(
            $district->streets()->orderBy('name')->get(['id', 'name'])
        );
    }
}
