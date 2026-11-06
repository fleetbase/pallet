<?php

namespace Fleetbase\Pallet\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Requests\CreateSupplierRequest;
use Fleetbase\Pallet\Http\Requests\UpdateSupplierRequest;
use Fleetbase\Pallet\Http\Resources\v1\DeletedResource;
use Fleetbase\Pallet\Http\Resources\v1\Supplier as SupplierResource;
use Fleetbase\Pallet\Models\Supplier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    protected array $writable = [
        'name',
        'email',
        'phone',
        'type',
        'status',
        'country',
        'website_url',
        'internal_id',
        'meta',
    ];

    public function create(CreateSupplierRequest $request)
    {
        $supplier = Supplier::create(array_merge($request->only($this->writable), [
            'company_uuid' => session('company'),
        ]));

        return new SupplierResource($supplier->fresh(['place']));
    }

    public function update($id, UpdateSupplierRequest $request)
    {
        try {
            $supplier = Supplier::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $supplier->update($request->only($this->writable));

        return new SupplierResource($supplier->fresh(['place']));
    }

    public function query(Request $request)
    {
        return SupplierResource::collection(Supplier::queryWithRequest($request));
    }

    public function find($id, Request $request)
    {
        try {
            $supplier = Supplier::findRecordOrFail($id, ['place']);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        return new SupplierResource($supplier);
    }

    public function delete($id, Request $request)
    {
        try {
            $supplier = Supplier::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $supplier->delete();

        return new DeletedResource($supplier);
    }

    protected function notFound(): JsonResponse
    {
        return response()->json(['error' => 'Supplier resource not found.'], 404);
    }
}
