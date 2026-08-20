<?php

namespace Fleetbase\Pallet\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Resources\v1\Audit as AuditResource;
use Fleetbase\Pallet\Models\Audit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * Read-only by design. Audit entries are written by the system as operations
 * happen; an entry a consumer could author would not be a record of anything.
 */
class AuditController extends Controller
{
    public function query(Request $request)
    {
        return AuditResource::collection(
            Audit::queryWithRequest($request, function ($query) {
                $query->with('auditable');
            })
        );
    }

    public function find($id, Request $request)
    {
        try {
            $audit = Audit::findRecordOrFail($id, ['auditable']);
        } catch (ModelNotFoundException $exception) {
            return response()->json(['error' => 'Audit resource not found.'], 404);
        }

        return new AuditResource($audit);
    }
}
