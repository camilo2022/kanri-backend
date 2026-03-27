<?php

namespace App\Http\Controllers;

use App\Http\Requests\Eps\EpsAllRequest;
use App\Http\Requests\Eps\EpsDeleteRequest;
use App\Http\Requests\Eps\EpsFindRequest;
use App\Http\Requests\Eps\EpsRestoreRequest;
use App\Http\Requests\Eps\EpsStoreRequest;
use App\Http\Requests\Eps\EpsUpdateRequest;
use App\Http\Resources\Eps\EpsCollection;
use App\Models\Eps;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class EpsController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(EpsAllRequest $request)
    {
        try {
            $epss = Eps::when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $epss = $epss->paginate($request->integer('per_page', $epss->count()));

            return $this->successResponse(
                new EpsCollection($epss),
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

    public function find(EpsFindRequest $request, $id)
    {
        try {
            $eps = Eps::findOrFail($id);

            return $this->successResponse(
                [
                    'eps' => $eps
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

    public function store(EpsStoreRequest $request)
    {
        try {
            $eps = new Eps();
            $eps->name = $request->input('name');
            $eps->description = $request->input('description');
            $eps->save();

            return $this->successResponse(
                [
                    'eps' => $eps
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

    public function update(EpsUpdateRequest $request, $id)
    {
        try {
            $eps = Eps::findOrFail($id);
            $eps->name = $request->input('name');
            $eps->description = $request->input('description');
            $eps->save();

            return $this->successResponse(
                [
                    'eps' => $eps
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

    public function delete(EpsDeleteRequest $request, $id)
    {
        try {
            $eps = Eps::findOrFail($id);
            $eps->delete();

            return $this->successResponse(
                [
                    'eps' => $eps
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

    public function restore(EpsRestoreRequest $request, $id)
    {
        try {
            $eps = Eps::withTrashed()->findOrFail($id);
            $eps->restore();

            return $this->successResponse(
                [
                    'eps' => $eps
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
