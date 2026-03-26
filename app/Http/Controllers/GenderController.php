<?php

namespace App\Http\Controllers;

use App\Http\Requests\Gender\GenderAllRequest;
use App\Http\Requests\Gender\GenderDeleteRequest;
use App\Http\Requests\Gender\GenderFindRequest;
use App\Http\Requests\Gender\GenderRestoreRequest;
use App\Http\Requests\Gender\GenderStoreRequest;
use App\Http\Requests\Gender\GenderUpdateRequest;
use App\Http\Resources\Gender\GenderCollection;
use App\Models\Gender;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class GenderController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(GenderAllRequest $request)
    {
        try {
            $genders = Gender::when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $genders = $genders->paginate($request->integer('per_page', $genders->count()));

            return $this->successResponse(
                new GenderCollection($genders),
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function find(GenderFindRequest $request, $id)
    {
        try {
            $gender = Gender::findOrFail($id);

            return $this->successResponse(
                [
                    'gender' => $gender
                ],
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function store(GenderStoreRequest $request)
    {
        try {
            $gender = new Gender();
            $gender->name = $request->input('name');
            $gender->description = $request->input('description');
            $gender->save();

            return $this->successResponse(
                [
                    'gender' => $gender
                ],
                $this->getMessage('Success'),
                201
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function update(GenderUpdateRequest $request, $id)
    {
        try {
            $gender = Gender::findOrFail($id);
            $gender->name = $request->input('name');
            $gender->description = $request->input('description');
            $gender->save();

            return $this->successResponse(
                [
                    'gender' => $gender
                ],
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function delete(GenderDeleteRequest $request, $id)
    {
        try {
            $gender = Gender::findOrFail($id);
            $gender->delete();

            return $this->successResponse(
                [
                    'gender' => $gender
                ],
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    public function restore(GenderRestoreRequest $request, $id)
    {
        try {
            $gender = Gender::withTrashed()->findOrFail($id);
            $gender->restore();

            return $this->successResponse(
                [
                    'gender' => $gender
                ],
                $this->getMessage('Success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }
}
