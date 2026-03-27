<?php

namespace App\Http\Controllers;

use App\Http\Requests\PensionFund\PensionFundAllRequest;
use App\Http\Requests\PensionFund\PensionFundDeleteRequest;
use App\Http\Requests\PensionFund\PensionFundFindRequest;
use App\Http\Requests\PensionFund\PensionFundRestoreRequest;
use App\Http\Requests\PensionFund\PensionFundStoreRequest;
use App\Http\Requests\PensionFund\PensionFundUpdateRequest;
use App\Http\Resources\PensionFund\PensionFundCollection;
use App\Models\PensionFund;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;

class PensionFundController extends Controller
{
    use ApiMessage, ApiResponser;

    public function all(PensionFundAllRequest $request)
    {
        try {
            $pension_funds = PensionFund::when($request->filled('search'), function ($query) use ($request) {
                    return $query->search($request->input('search'));
                })
                ->when($request->boolean('with_trashed'), function ($query) {
                    return $query->withTrashed();
                })
                ->when($request->filled('column') && $request->filled('dir'), function ($query) use ($request) {
                    return $query->orderBy($request->input('column'), $request->input('dir'));
                });

            $pension_funds = $pension_funds->paginate($request->integer('per_page', $pension_funds->count()));

            return $this->successResponse(
                new PensionFundCollection($pension_funds),
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

    public function find(PensionFundFindRequest $request, $id)
    {
        try {
            $pension_fund = PensionFund::findOrFail($id);

            return $this->successResponse(
                [
                    'pension_fund' => $pension_fund
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

    public function store(PensionFundStoreRequest $request)
    {
        try {
            $pension_fund = new PensionFund();
            $pension_fund->name = $request->input('name');
            $pension_fund->description = $request->input('description');
            $pension_fund->save();

            return $this->successResponse(
                [
                    'pension_fund' => $pension_fund
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

    public function update(PensionFundUpdateRequest $request, $id)
    {
        try {
            $pension_fund = PensionFund::findOrFail($id);
            $pension_fund->name = $request->input('name');
            $pension_fund->description = $request->input('description');
            $pension_fund->save();

            return $this->successResponse(
                [
                    'pension_fund' => $pension_fund
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

    public function delete(PensionFundDeleteRequest $request, $id)
    {
        try {
            $pension_fund = PensionFund::findOrFail($id);
            $pension_fund->delete();

            return $this->successResponse(
                [
                    'pension_fund' => $pension_fund
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

    public function restore(PensionFundRestoreRequest $request, $id)
    {
        try {
            $pension_fund = PensionFund::withTrashed()->findOrFail($id);
            $pension_fund->restore();

            return $this->successResponse(
                [
                    'pension_fund' => $pension_fund
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
