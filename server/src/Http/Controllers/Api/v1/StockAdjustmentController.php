<?php

namespace Fleetbase\Pallet\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Controllers\StockAdjustmentController as InternalStockAdjustmentController;
use Fleetbase\Pallet\Http\Requests\CreateStockAdjustmentRequest;
use Fleetbase\Pallet\Http\Resources\v1\StockAdjustment as StockAdjustmentResource;
use Fleetbase\Pallet\Models\StockAdjustment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Adjustments are how stock moves outside of an order — corrections, damage,
 * shrinkage, found stock.
 *
 * There is no update or delete: an adjustment is a ledger entry describing
 * something that happened. Correcting one means making another, which is why the
 * history shows both.
 */
class StockAdjustmentController extends Controller
{
    protected array $relations = ['product', 'variant', 'warehouse', 'inventory'];

    /**
     * Delegates the stock mechanics — the row locking, the inventory row creation for
     * an `add` against a product not yet stocked, the before/after capture and the
     * ledger write all live in the console's implementation.
     */
    public function create(CreateStockAdjustmentRequest $request)
    {
        $delegated = Request::create($request->getPathInfo(), 'POST', [
            'stock_adjustment' => [
                'type'           => $request->input('type', 'correction'),
                'quantity'       => (int) $request->input('quantity'),
                'reason'         => $request->input('reason'),
                'product_uuid'   => $request->input('product'),
                'variant_uuid'   => $request->input('variant'),
                'warehouse_uuid' => $request->input('warehouse'),
            ],
        ]);
        $delegated->setLaravelSession($request->session());
        $delegated->setRouteResolver($request->getRouteResolver());

        $result = (new InternalStockAdjustmentController())->createRecord($delegated);

        if ($result instanceof JsonResponse) {
            return $this->normalizeError($result);
        }

        $adjustment = StockAdjustment::where('company_uuid', session('company'))
            ->latest('created_at')
            ->with($this->relations)
            ->first();

        return new StockAdjustmentResource($adjustment);
    }

    public function query(Request $request)
    {
        return StockAdjustmentResource::collection(
            StockAdjustment::queryWithRequest($request, function ($query) {
                $query->with($this->relations);
            })
        );
    }

    public function find($id, Request $request)
    {
        try {
            $adjustment = StockAdjustment::findRecordOrFail($id, $this->relations);
        } catch (ModelNotFoundException $exception) {
            return response()->json(['error' => 'Stock adjustment resource not found.'], 404);
        }

        return new StockAdjustmentResource($adjustment);
    }

    protected function normalizeError(JsonResponse $response): JsonResponse
    {
        $payload = $response->getData(true);
        $message = data_get($payload, 'errors.0') ?? data_get($payload, 'error') ?? 'Stock adjustment could not be recorded.';

        return response()->json(['error' => $message], $response->getStatusCode());
    }
}
