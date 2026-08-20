<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\Audit as AuditResource;
use Fleetbase\Pallet\Models\Audit;
use Fleetbase\Pallet\Models\AuditEventType;
use Fleetbase\Support\Http;
use Illuminate\Http\Request;

/**
 * AuditController — Read-only controller for the WMS operational audit trail.
 *
 * This controller exposes only `index` and `show` endpoints. Audit entries are
 * immutable — they are written programmatically by the system via the AuditService
 * when significant warehouse operational events occur. No create, update, or
 * delete operations are permitted via the API.
 */
class AuditController extends PalletResourceController
{
    /**
     * The package namespace used to resolve from.
     */
    public string $namespace = '\\Fleetbase\\Pallet';

    /**
     * The resource to query.
     *
     * @var string
     */
    public $resource = 'audit';

    /**
     * List all audit trail entries for the authenticated company.
     *
     * Supports filtering via query parameters:
     *   - event_type    : Filter by event category (e.g. stock_adjustment, cycle_count)
     *   - type          : Filter by secondary classification
     *   - auditable_uuid: Filter by the subject model UUID
     *   - auditable_type: Filter by the subject model class
     *   - performed_by  : Filter by the user UUID who performed the action
     *   - search        : Full-text search across action, reason, comments
     *   - sort          : Sort field (default: created_at)
     *   - order         : Sort direction (default: desc)
     *   - limit         : Number of results per page (default: 30)
     *   - page          : Page number
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Audit::where('company_uuid', session('company'))
                      ->with(['performedBy', 'createdBy'])
                      ->orderBy('created_at', 'desc');

        // Filter by event_type
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        // Filter by secondary type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by auditable subject UUID
        if ($request->filled('auditable_uuid')) {
            $query->where('auditable_uuid', $request->input('auditable_uuid'));
        }

        // Filter by auditable subject type
        $auditableType = $request->input('auditable_type', $request->input('subject_type'));
        if (filled($auditableType)) {
            $query->where('auditable_type', $auditableType);
        }

        // Filter by the user who performed the action
        if ($request->filled('performed_by')) {
            $query->where('performed_by_uuid', $request->input('performed_by'));
        }

        // Full-text search
        $search = $request->input('search', $request->input('query'));
        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('comments', 'like', "%{$search}%")
                  ->orWhere('event_type', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField    = $request->input('sort', 'created_at');
        $sortOrder    = $request->input('order', 'desc');
        $allowedSorts = ['created_at', 'event_type', 'action', 'type', 'completed_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $limit  = (int) $request->input('limit', 30);
        $limit  = min(max($limit, 1), 100);
        $audits = $query->paginate($limit);

        // This controller builds its own query, so it also has to apply the
        // resource envelope the rest of the API relies on. Without it the
        // collection falls back to Laravel's default `data` key, which the
        // Ember store cannot match — the audits screen rendered nothing.
        if (Http::isInternalRequest($request)) {
            AuditResource::wrap((new Audit())->getPluralName());
        }

        return AuditResource::collection($audits);
    }

    /**
     * Show a single audit trail entry by its public_id or UUID.
     *
     * @return AuditResource|\Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        $audit = Audit::where('company_uuid', session('company'))
                      ->where(function ($q) use ($id) {
                          $q->where('public_id', $id)
                            ->orWhere('uuid', $id);
                      })
                      ->with(['performedBy', 'createdBy'])
                      ->first();

        if (!$audit) {
            return response()->json(['error' => 'Audit record not found.'], 404);
        }

        return new AuditResource($audit);
    }

    /**
     * Return all available event type constants for use in frontend filter dropdowns.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function eventTypes()
    {
        return response()->json([
            'eventTypes' => AuditEventType::all(),
        ]);
    }
}
