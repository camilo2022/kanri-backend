<?php

namespace App\Http\Controllers;

use App\Http\Requests\Arl\ArlAllRequest;
use App\Http\Requests\Arl\ArlDeleteRequest;
use App\Http\Requests\Arl\ArlFindRequest;
use App\Http\Requests\Arl\ArlRestoreRequest;
use App\Http\Requests\Arl\ArlStoreRequest;
use App\Http\Requests\Arl\ArlUpdateRequest;
use App\Http\Resources\Arl\ArlCollection;
use App\Models\Arl;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class ArlController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(ArlAllRequest $request)
    {
        try {
            $arls = Arl::when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $arls = $arls->paginate($request->integer('per_page', $arls->count()));

            return $this->successResponse(
                new ArlCollection($arls),
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

    public function find(ArlFindRequest $request, $id)
    {
        try {
            $arl = Arl::findOrFail($id);

            return $this->successResponse(
                [
                    'arl' => $arl
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

    public function store(ArlStoreRequest $request)
    {
        try {
            $arl = new Arl();
            $arl->name = $request->input('name');
            $arl->description = $request->input('description');
            $arl->save();

            return $this->successResponse(
                [
                    'arl' => $arl
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

    public function update(ArlUpdateRequest $request, $id)
    {
        try {
            $arl = Arl::findOrFail($id);
            $arl->name = $request->input('name');
            $arl->description = $request->input('description');
            $arl->save();

            return $this->successResponse(
                [
                    'arl' => $arl
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

    public function delete(ArlDeleteRequest $request, $id)
    {
        try {
            $arl = Arl::findOrFail($id);
            $arl->delete();

            return $this->successResponse(
                [
                    'arl' => $arl
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

    public function restore(ArlRestoreRequest $request, $id)
    {
        try {
            $arl = Arl::withTrashed()->findOrFail($id);
            $arl->restore();

            return $this->successResponse(
                [
                    'arl' => $arl
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
