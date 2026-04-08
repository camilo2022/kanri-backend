<?php

namespace App\Http\Controllers;

use App\Http\Requests\Trademark\TrademarkSettingRequest;
use App\Http\Requests\Trademark\TrademarkAllRequest;
use App\Http\Requests\Trademark\TrademarkDeleteRequest;
use App\Http\Requests\Trademark\TrademarkFindRequest;
use App\Http\Requests\Trademark\TrademarkRestoreRequest;
use App\Http\Requests\Trademark\TrademarkStoreRequest;
use App\Http\Requests\Trademark\TrademarkUpdateRequest;
use App\Http\Resources\Trademark\TrademarkCollection;
use App\Models\Trademark;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class TrademarkController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(TrademarkAllRequest $request)
    {
        try {
            $trademakers = Trademark::when($request->filled('search'), function ($query) use ($request) {
                return $query->search($request->input('search'));
            })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $trademakers = $trademakers->paginate($request->integer('per_page', $trademakers->count()));

            return $this->successResponse(
                new TrademarkCollection($trademakers),
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

    public function find(TrademarkFindRequest $request, $id)
    {
        try {
            $trademark = Trademark::findOrFail($id);

            return $this->successResponse(
                [
                    'trademark' => $trademark
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

    public function store(TrademarkStoreRequest $request)
    {
        try {
            $trademark = new Trademark();
            $trademark->name = $request->input('name');
            $trademark->description = $request->input('description');
            $trademark->save();

            return $this->successResponse(
                [
                    'trademark' => $trademark
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

    public function update(TrademarkUpdateRequest $request, $id)
    {
        try {
            $trademark = Trademark::findOrFail($id);
            $trademark->name = $request->input('name');
            $trademark->description = $request->input('description');
            $trademark->save();

            return $this->successResponse(
                [
                    'trademark' => $trademark
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

    public function delete(TrademarkDeleteRequest $request, $id)
    {
        try {
            $trademark = Trademark::findOrFail($id);
            $trademark->delete();

            return $this->successResponse(
                [
                    'trademark' => $trademark
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

    public function restore(TrademarkRestoreRequest $request, $id)
    {
        try {
            $trademark = Trademark::withTrashed()->findOrFail($id);
            $trademark->restore();

            return $this->successResponse(
                [
                    'trademark' => $trademark
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

    public function setting(TrademarkSettingRequest $request, $id)
    {
        try {
            $trademark = Trademark::findOrFail($id);
            $trademark->settings = $request->input('settings');

            $trademark->save();

            return $this->successResponse(
                [
                    'trademark' => $trademark
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
