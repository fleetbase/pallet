<?php

namespace Fleetbase\Pallet\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Resources\v1\Batch as BatchResource;
use Fleetbase\Pallet\Models\Batch;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * Read-only: batches are produced by receiving stock, so creating one directly
 * would describe a lot that never arrived.
 */
class BatchController extends Controller
{
    protected array $relations = ['product', 'variant'];

    public function query(Request $request)
    {
        return BatchResource::collection(
            Batch::queryWithRequest($request, function ($query) {
                $query->with($this->relations);
            })
        );
    }

    public function find($id, Request $request)
    {
        try {
            $batch = Batch::findRecordOrFail($id, $this->relations);
        } catch (ModelNotFoundException $exception) {
            return response()->json(['error' => 'Batch resource not found.'], 404);
        }

        return new BatchResource($batch);
    }
}
