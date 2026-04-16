<?php

namespace App\Http\Controllers;

use App\Http\Requests\Audit\AuditAllRequest;
use App\Http\Resources\Audit\AuditCollection;
use OwenIt\Auditing\Models\Audit;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Carbon\Carbon;

class AuditController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(AuditAllRequest $request)
    {
        try {
            $audits = Audit::with(['user' => ['employee' => ['person', 'position' => ['area']]], 'auditable'])
                ->when($request->filled('user_id'), function ($query) use ($request) {
                    return $query->where('user_id', $request->integer('user_id'));
                })
                ->when($request->filled('event'), function ($query) use ($request) {
                    return $query->where('event', $request->input('event'));
                })
                ->when($request->filled('auditable_name'), function ($query) use ($request) {
                    return $query->where('auditable_type', $request->input('auditable_name'));
                })
                ->when($request->filled('start_date') || $request->filled('end_date'), function ($query) use ($request) {
                    return $query->whereBetween('created_at', [
                        Carbon::parse($request->input('start_date'))->startOfDay(),
                        Carbon::parse($request->input('end_date'))->endOfDay()
                    ]);
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $audits = $audits->paginate($request->integer('per_page', $audits->count()));

            return $this->successResponse(
                new AuditCollection($audits),
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

    public function all_elements(AuditAllRequest $request)
    {
        try {
            $audits = Audit::with(['user' => ['employee' => ['person', 'position' => ['area']]], 'auditable'])
                ->when($request->filled('user_id'), function ($query) use ($request) {
                    return $query->where('user_id', $request->integer('user_id'));
                })
                ->when($request->filled('event'), function ($query) use ($request) {
                    return $query->where('event', $request->input('event'));
                })
                ->when($request->filled('auditable_name'), function ($query) use ($request) {
                    return $query->where('auditable_type', $request->input('auditable_name'));
                })
                ->when($request->filled('start_date') || $request->filled('end_date'), function ($query) use ($request) {
                    return $query->whereBetween('created_at', [
                        Carbon::parse($request->input('start_date'))->startOfDay(),
                        Carbon::parse($request->input('end_date'))->endOfDay()
                    ]);
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $audits = $audits->paginate($request->integer('per_page', $audits->count()));

            return $this->successResponse(
                new AuditCollection($audits),
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
