<?php

namespace App\Http\Controllers;

use App\Http\Requests\Workflow\Process\ProcessAllRequest;
use App\Http\Requests\Workflow\Process\ProcessDeleteRequest;
use App\Http\Requests\Workflow\Process\ProcessFindRequest;
use App\Http\Requests\Workflow\Process\ProcessRestoreRequest;
use App\Http\Requests\Workflow\Process\ProcessStoreRequest;
use App\Http\Requests\Workflow\Process\ProcessUpdateRequest;
use App\Http\Resources\Workflow\Process\ProcessCollection;
use App\Models\Process;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class ProcessController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(ProcessAllRequest $request)
    {
        try {
            $processes = Process::with(['before_processes', 'after_processes', 'subprocesses'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('process_id'), function ($query) use ($request) {
                    return $query->where('id', '<>', $request->integer('process_id'));
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $processes = $processes->paginate($request->integer('per_page', $processes->count()));

            return $this->successResponse(
                new ProcessCollection($processes),
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

    public function find(ProcessFindRequest $request, $id)
    {
        try {
            $process = Process::with(['before_processes', 'after_processes'])
                ->findOrFail($id);

            return $this->successResponse(
                [
                    'process' => $process
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

    public function store(ProcessStoreRequest $request)
    {
        try {
            $process = new Process();
            $process->name = $request->input('name');
            $process->description = $request->input('description');
            $process->settings = $request->input('settings');

            $process->save();

            $process->auditAttach('after_processes', $request->array('after_processes'));

            return $this->successResponse(
                [
                    'process' => $process
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

    public function update(ProcessUpdateRequest $request, $id)
    {
        try {
            $process = Process::findOrFail($id);
            $process->name = $request->input('name');
            $process->description = $request->input('description');
            $process->settings = $request->input('settings');

            $process->save();

            $process->auditSync('after_processes', $request->array('after_processes'));

            return $this->successResponse(
                [
                    'process' => $process
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

    public function delete(ProcessDeleteRequest $request, $id)
    {
        try {
            $process = Process::findOrFail($id);
            $process->delete();

            return $this->successResponse(
                [
                    'process' => $process
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

    public function restore(ProcessRestoreRequest $request, $id)
    {
        try {
            $process = Process::withTrashed()->findOrFail($id);
            $process->restore();

            return $this->successResponse(
                [
                    'process' => $process
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
