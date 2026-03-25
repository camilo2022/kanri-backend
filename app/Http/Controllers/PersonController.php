<?php

namespace App\Http\Controllers;

use App\Http\Requests\Person\PersonAllRequest;
use App\Http\Requests\Person\PersonDeleteRequest;
use App\Http\Requests\Person\PersonFindRequest;
use App\Http\Requests\Person\PersonRestoreRequest;
use App\Http\Requests\Person\PersonStoreRequest;
use App\Http\Requests\Person\PersonUpdateRequest;
use App\Http\Resources\Person\PersonCollection;
use App\Models\Person;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class PersonController extends Controller
{
    use ApiMessage, ApiResponser;
    
    public function all(PersonAllRequest $request)
    {
        try {
            $people = Person::with(['employee'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $people = $people->paginate($request->integer('per_page', $people->count()));

            return $this->successResponse(
                new PersonCollection($people),
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

    public function find(PersonFindRequest $request, $id)
    {
        try {
            $person = Person::with(['employee'])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'person' => $person
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
    
    public function store(PersonStoreRequest $request)
    {
        try {
            $person = new Person();
            $person->document = $request->input('document');
            $person->names = $request->input('names');
            $person->last_names = $request->input('last_names');
            $person->gender_id = $request->integer('gender_id');
            $person->birth_date = $request->input('birth_date');
            $person->blood_type_id = $request->integer('blood_type_id');
            $person->address = $request->input('address');
            $person->phone = $request->input('phone');
            $person->save();

            return $this->successResponse(
                [
                    'person' => $person
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
    
    public function update(PersonUpdateRequest $request, $id)
    {
        try {
            $person = Person::findOrFail($id);
            $person->document = $request->input('document');
            $person->names = $request->input('names');
            $person->last_names = $request->input('last_names');
            $person->gender_id = $request->integer('gender_id');
            $person->birth_date = $request->input('birth_date');
            $person->blood_type_id = $request->integer('blood_type_id');
            $person->address = $request->input('address');
            $person->phone = $request->input('phone');
            $person->save();

            return $this->successResponse(
                [
                    'person' => $person
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

    public function delete(PersonDeleteRequest $request, $id)
    {
        try {
            $person = Person::findOrFail($id);
            $person->delete();

            return $this->successResponse(
                [
                    'person' => $person
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

    public function restore(PersonRestoreRequest $request, $id)
    {
        try {
            $person = Person::withTrashed()->findOrFail($id);
            $person->restore();

            return $this->successResponse(
                [
                    'person' => $person
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
