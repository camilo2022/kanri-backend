<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\Country\CountryAllRequest;
use App\Http\Requests\Location\Country\CountrySettingsRequest;
use App\Http\Resources\Location\Country\CountryCollection;
use App\Http\Resources\Location\Country\CountryResource;
use App\Models\Country;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class CountryController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(CountryAllRequest $request)
    {
        try {
            $countries = Country::query()
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->sort($request->input('column'), $request->input('dir'));
                }, function ($query) {
                    return $query->orderBy('name');
                });

            $countries = $countries->paginate($request->integer('per_page', $countries->count()));

            return $this->successResponse(
                new CountryCollection($countries),
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

    public function settings(CountrySettingsRequest $request, $id)
    {
        try {
            $country = Country::findOrFail($id);
            $country->settings = $request->input('settings');
            $country->save();

            return $this->successResponse(
                new CountryResource($country),
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
