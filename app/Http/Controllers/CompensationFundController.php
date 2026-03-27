<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompensationFund\CompensationFundAllRequest;
use App\Http\Requests\CompensationFund\CompensationFundDeleteRequest;
use App\Http\Requests\CompensationFund\CompensationFundFindRequest;
use App\Http\Requests\CompensationFund\CompensationFundRestoreRequest;
use App\Http\Requests\CompensationFund\CompensationFundStoreRequest;
use App\Http\Requests\CompensationFund\CompensationFundUpdateRequest;
use App\Http\Resources\CompensationFund\CompensationFundCollection;
use App\Models\CompensationFund;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class CompensationFundController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(CompensationFundAllRequest $request)
    {
        try {
            $compensation_funds = CompensationFund::when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $compensation_funds = $compensation_funds->paginate($request->integer('per_page', $compensation_funds->count()));

            return $this->successResponse(
                new CompensationFundCollection($compensation_funds),
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

    public function find(CompensationFundFindRequest $request, $id)
    {
        try {
            $compensation_fund = CompensationFund::findOrFail($id);

            return $this->successResponse(
                [
                    'compensation_fund' => $compensation_fund
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

    public function store(CompensationFundStoreRequest $request)
    {
        try {
            $compensation_fund = new CompensationFund();
            $compensation_fund->name = $request->input('name');
            $compensation_fund->description = $request->input('description');
            $compensation_fund->save();

            return $this->successResponse(
                [
                    'compensation_fund' => $compensation_fund
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

    public function update(CompensationFundUpdateRequest $request, $id)
    {
        try {
            $compensation_fund = CompensationFund::findOrFail($id);
            $compensation_fund->name = $request->input('name');
            $compensation_fund->description = $request->input('description');
            $compensation_fund->save();

            return $this->successResponse(
                [
                    'compensation_fund' => $compensation_fund
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

    public function delete(CompensationFundDeleteRequest $request, $id)
    {
        try {
            $compensation_fund = CompensationFund::findOrFail($id);
            $compensation_fund->delete();

            return $this->successResponse(
                [
                    'compensation_fund' => $compensation_fund
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

    public function restore(CompensationFundRestoreRequest $request, $id)
    {
        try {
            $compensation_fund = CompensationFund::withTrashed()->findOrFail($id);
            $compensation_fund->restore();

            return $this->successResponse(
                [
                    'compensation_fund' => $compensation_fund
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
