<?php

namespace App\Http\Controllers;

use App\Http\Requests\WashTone\WashToneAllRequest;
use App\Http\Requests\WashTone\WashToneDeleteRequest;
use App\Http\Requests\WashTone\WashToneFindRequest;
use App\Http\Requests\WashTone\WashToneRestoreRequest;
use App\Http\Requests\WashTone\WashToneStoreRequest;
use App\Http\Requests\WashTone\WashToneUpdateRequest;
use App\Http\Resources\WashTone\WashToneCollection;
use App\Models\WashTone;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class WashToneController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(WashToneAllRequest $request)
    {
        try {
            $wash_tones = WashTone::with(['item'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $wash_tones = $wash_tones->paginate($request->integer('per_page', $wash_tones->count()));

            return $this->successResponse(
                new WashToneCollection($wash_tones),
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

    public function find(WashToneFindRequest $request, $id)
    {
        try {
            $wash_tone = WashTone::with(['item'])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'wash_tone' => $wash_tone
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

    public function store(WashToneStoreRequest $request)
    {
        try {
            $wash_tone = new WashTone();
            $wash_tone->name = $request->input('name');
            $wash_tone->description = $request->input('description');
            $wash_tone->settings = $request->input('settings');

            $wash_tone->save();

            return $this->successResponse(
                [
                    'wash_tone' => $wash_tone
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

    public function update(WashToneUpdateRequest $request, $id)
    {
        try {
            $wash_tone = WashTone::findOrFail($id);
            $wash_tone->name = $request->input('name');
            $wash_tone->description = $request->input('description');
            $wash_tone->settings = $request->input('settings');

            $wash_tone->save();

            return $this->successResponse(
                [
                    'wash_tone' => $wash_tone
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

    public function delete(WashToneDeleteRequest $request, $id)
    {
        try {
            $wash_tone = WashTone::findOrFail($id);
            $wash_tone->delete();

            return $this->successResponse(
                [
                    'wash_tone' => $wash_tone
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

    public function restore(WashToneRestoreRequest $request, $id)
    {
        try {
            $wash_tone = WashTone::withTrashed()->findOrFail($id);
            $wash_tone->restore();

            return $this->successResponse(
                [
                    'wash_tone' => $wash_tone
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
