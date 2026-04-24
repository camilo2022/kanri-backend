<?php

namespace App\Http\Controllers;

use App\Http\Requests\Workflow\Subprocess\SubprocessAllRequest;
use App\Http\Requests\Workflow\Subprocess\SubprocessDeleteRequest;
use App\Http\Requests\Workflow\Subprocess\SubprocessFindRequest;
use App\Http\Requests\Workflow\Subprocess\SubprocessRestoreRequest;
use App\Http\Requests\Workflow\Subprocess\SubprocessStoreRequest;
use App\Http\Requests\Workflow\Subprocess\SubprocessUpdateRequest;
use App\Http\Resources\Workflow\Subprocess\SubprocessCollection;
use App\Models\Subprocess;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class SubprocessController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(SubprocessAllRequest $request, $process_id)
    {
        try {
            $subprocesses = Subprocess::with(['process'])
                ->whereHas('process', fn($r) => $r->where('id', $process_id))
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->integer('process_id'), function ($query) use ($request) {
                    return $query->where('id', '!=', $request->integer('process_id'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $subprocesses = $subprocesses->paginate($request->integer('per_page', $subprocesses->count()));

            return $this->successResponse(
                new SubprocessCollection($subprocesses),
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

    public function find(SubprocessFindRequest $request, $id)
    {
        try {
            $subprocess = Subprocess::with(['process'])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'subprocess' => $subprocess
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

    public function store(SubprocessStoreRequest $request)
    {
        try {
            $subprocess = new Subprocess();
            $subprocess->name = $request->input('name');
            $subprocess->description = $request->input('description');

            $subprocess->save();

            $subprocess->auditSync('process', $request->integer('process_id'));

            return $this->successResponse(
                [
                    'subprocess' => $subprocess
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

    public function update(SubprocessUpdateRequest $request, $id)
    {
        try {
            $subprocess = Subprocess::findOrFail($id);
            $subprocess->name = $request->input('name');
            $subprocess->description = $request->input('description');

            $subprocess->save();

            return $this->successResponse(
                [
                    'subprocess' => $subprocess
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

    public function delete(SubprocessDeleteRequest $request, $id)
    {
        try {
            $subprocess = Subprocess::findOrFail($id);
            $subprocess->delete();

            return $this->successResponse(
                [
                    'subprocess' => $subprocess
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

    public function restore(SubprocessRestoreRequest $request, $id)
    {
        try {
            $subprocess = Subprocess::withTrashed()->findOrFail($id);
            $subprocess->restore();

            return $this->successResponse(
                [
                    'subprocess' => $subprocess
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
