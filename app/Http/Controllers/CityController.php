<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\City\CityAllRequest;
use App\Http\Resources\Location\City\CityCollection;
use App\Models\City;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class CityController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(CityAllRequest $request, $department_id)
    {
        try {
            $cities = City::where('department_id', $request->integer('department_id'))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                }, function ($query) {
                    return $query->orderBy('name');
                });

            $cities = $cities->paginate($request->integer('per_page', $cities->count()));

            return $this->successResponse(
                new CityCollection($cities),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage(),
                ],
                $this->getCode(class_basename($e))
            );
        }
    }
}
