<?php

namespace App\Http\Controllers;

use App\Http\Requests\RiskManager\RiskManagerAllRequest;
use App\Http\Requests\RiskManager\RiskManagerDeleteRequest;
use App\Http\Requests\RiskManager\RiskManagerFindRequest;
use App\Http\Requests\RiskManager\RiskManagerRestoreRequest;
use App\Http\Requests\RiskManager\RiskManagerStoreRequest;
use App\Http\Requests\RiskManager\RiskManagerUpdateRequest;
use App\Http\Resources\RiskManager\RiskManagerCollection;
use App\Models\RiskManager;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class RiskManagerController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(RiskManagerAllRequest $request)
    {
        try {
            $risk_managers = RiskManager::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $risk_managers = $risk_managers->paginate($request->integer('per_page', $risk_managers->count()));

            return $this->successResponse(
                new RiskManagerCollection($risk_managers),
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

    public function find(RiskManagerFindRequest $request, $id)
    {
        try {
            $risk_manager = RiskManager::findOrFail($id);

            return $this->successResponse(
                [
                    'risk_manager' => $risk_manager
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

    public function store(RiskManagerStoreRequest $request)
    {
        try {
            $risk_manager = new RiskManager();
            $risk_manager->name = $request->input('name');
            $risk_manager->description = $request->input('description');
            $risk_manager->save();

            return $this->successResponse(
                [
                    'risk_manager' => $risk_manager
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

    public function update(RiskManagerUpdateRequest $request, $id)
    {
        try {
            $risk_manager = RiskManager::findOrFail($id);
            $risk_manager->name = $request->input('name');
            $risk_manager->description = $request->input('description');
            $risk_manager->save();

            return $this->successResponse(
                [
                    'risk_manager' => $risk_manager
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

    public function delete(RiskManagerDeleteRequest $request, $id)
    {
        try {
            $risk_manager = RiskManager::findOrFail($id);
            $risk_manager->delete();

            return $this->successResponse(
                [
                    'risk_manager' => $risk_manager
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

    public function restore(RiskManagerRestoreRequest $request, $id)
    {
        try {
            $risk_manager = RiskManager::withTrashed()->findOrFail($id);
            $risk_manager->restore();

            return $this->successResponse(
                [
                    'risk_manager' => $risk_manager
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
