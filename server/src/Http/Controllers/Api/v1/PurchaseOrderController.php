<?php

namespace Fleetbase\Pallet\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Controllers\PurchaseOrderController as InternalPurchaseOrderController;
use Fleetbase\Pallet\Http\Requests\CreatePurchaseOrderRequest;
use Fleetbase\Pallet\Http\Requests\UpdatePurchaseOrderRequest;
use Fleetbase\Pallet\Http\Resources\v1\DeletedResource;
use Fleetbase\Pallet\Http\Resources\v1\PurchaseOrder as PurchaseOrderResource;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Pallet\Models\PurchaseOrderItem;
use Fleetbase\Pallet\Models\Supplier;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    protected array $relations = ['supplier', 'warehouse', 'items', 'items.product', 'items.variant', 'items.warehouse'];

    protected array $writable = [
        'status',
        'reference_code',
        'reference_url',
        'description',
        'comments',
        'currency',
        'expected_delivery_at',
        'order_created_at',
        'meta',
    ];

    /**
     * An order and its lines are created together. An integrator placing an order has
     * the lines in hand, and creating the header alone would leave a purchase order
     * that cannot be received.
     */
    public function create(CreatePurchaseOrderRequest $request)
    {
        $supplier  = $this->resolveSupplier($request);
        $warehouse = $this->resolveWarehouse($request, 'warehouse');

        if ($supplier instanceof JsonResponse) {
            return $supplier;
        }

        if ($warehouse instanceof JsonResponse) {
            return $warehouse;
        }

        $lines = $this->resolveLines($request, $warehouse);

        if ($lines instanceof JsonResponse) {
            return $lines;
        }

        $order = DB::transaction(function () use ($request, $supplier, $warehouse, $lines) {
            $order = PurchaseOrder::create(array_merge($request->only($this->writable), [
                'company_uuid'     => session('company'),
                'supplier_uuid'    => $supplier?->uuid,
                'warehouse_uuid'   => $warehouse?->uuid,
                'status'           => $request->input('status', 'pending'),
                'order_created_at' => $request->input('order_created_at', now()),
            ]));

            foreach ($lines as $line) {
                PurchaseOrderItem::create(array_merge($line, [
                    'company_uuid'        => session('company'),
                    'purchase_order_uuid' => $order->uuid,
                ]));
            }

            return $order;
        });

        return new PurchaseOrderResource($order->fresh($this->relations));
    }

    public function update($id, UpdatePurchaseOrderRequest $request)
    {
        try {
            $order = PurchaseOrder::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $supplier  = $this->resolveSupplier($request);
        $warehouse = $this->resolveWarehouse($request, 'warehouse');

        if ($supplier instanceof JsonResponse) {
            return $supplier;
        }

        if ($warehouse instanceof JsonResponse) {
            return $warehouse;
        }

        $changes = $request->only($this->writable);

        if ($supplier) {
            $changes['supplier_uuid'] = $supplier->uuid;
        }

        if ($warehouse) {
            $changes['warehouse_uuid'] = $warehouse->uuid;
        }

        $order->update($changes);

        return new PurchaseOrderResource($order->fresh($this->relations));
    }

    public function query(Request $request)
    {
        return PurchaseOrderResource::collection(
            PurchaseOrder::queryWithRequest($request, function ($query) {
                $query->with($this->relations);
            })
        );
    }

    public function find($id, Request $request)
    {
        try {
            $order = PurchaseOrder::findRecordOrFail($id, $this->relations);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        return new PurchaseOrderResource($order);
    }

    public function delete($id, Request $request)
    {
        try {
            $order = PurchaseOrder::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $order->delete();

        return new DeletedResource($order);
    }

    /**
     * Receive stock against the order.
     *
     * Delegates to the console's implementation rather than repeating it. That is
     * where the row locking, the cap at outstanding quantity, the ledger entries and
     * the partial-versus-complete status transitions live, and a second copy of that
     * logic would be a second thing to keep correct.
     *
     * Only the edges are translated: lines are addressed as `id` here, and the
     * internal error envelope is normalised to the one the consumable API uses.
     */
    public function receive($id, Request $request)
    {
        $items = collect($request->input('items', []))->map(function ($item) {
            $item = (array) $item;

            if (isset($item['id']) && !isset($item['uuid'])) {
                $item['uuid'] = $item['id'];
            }

            return $item;
        })->all();

        $delegated = Request::create($request->getPathInfo(), 'POST', ['items' => $items]);
        $delegated->setLaravelSession($request->session());
        $delegated->setRouteResolver($request->getRouteResolver());

        $result = (new InternalPurchaseOrderController())->receive($delegated, $id);

        if ($result instanceof JsonResponse) {
            return $this->normalizeError($result);
        }

        try {
            $order = PurchaseOrder::findRecordOrFail($id, $this->relations);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        return new PurchaseOrderResource($order);
    }

    /**
     * The console returns {"errors": [...]}; the consumable API speaks {"error": "..."}.
     */
    protected function normalizeError(JsonResponse $response): JsonResponse
    {
        $payload = $response->getData(true);
        $message = data_get($payload, 'errors.0') ?? data_get($payload, 'error') ?? 'Purchase order could not be received.';

        return response()->json(['error' => $message], $response->getStatusCode());
    }

    protected function resolveSupplier(Request $request): Supplier|JsonResponse|null
    {
        if (!$request->filled('supplier')) {
            return null;
        }

        $supplier = Supplier::where('company_uuid', session('company'))
            ->where('public_id', $request->input('supplier'))
            ->first();

        return $supplier ?: response()->json(['error' => 'Supplier not found.'], 404);
    }

    protected function resolveWarehouse(Request $request, string $key): Warehouse|JsonResponse|null
    {
        if (!$request->filled($key)) {
            return null;
        }

        $warehouse = Warehouse::where('company_uuid', session('company'))
            ->where('public_id', $request->input($key))
            ->first();

        return $warehouse ?: response()->json(['error' => 'Warehouse not found.'], 404);
    }

    /**
     * Turn the submitted lines into insertable attributes, resolving each product by
     * public id. A line whose product does not resolve fails the whole request — a
     * partially created order would be worse than none.
     */
    protected function resolveLines(Request $request, ?Warehouse $orderWarehouse): array|JsonResponse
    {
        $lines = [];

        foreach ($request->input('items', []) as $item) {
            $item = (array) $item;

            $product = Product::where('company_uuid', session('company'))
                ->where('public_id', data_get($item, 'product'))
                ->first();

            if (!$product) {
                return response()->json(['error' => 'Product [' . data_get($item, 'product') . '] not found.'], 404);
            }

            $lineWarehouse = $orderWarehouse;

            if (!empty($item['warehouse'])) {
                $lineWarehouse = Warehouse::where('company_uuid', session('company'))
                    ->where('public_id', $item['warehouse'])
                    ->first();

                if (!$lineWarehouse) {
                    return response()->json(['error' => 'Warehouse [' . $item['warehouse'] . '] not found.'], 404);
                }
            }

            $quantity   = (int) data_get($item, 'quantity', 0);
            $unitPrice  = data_get($item, 'unit_price');

            $lines[] = [
                'product_uuid'      => $product->uuid,
                'warehouse_uuid'    => $lineWarehouse?->uuid,
                'sku'               => $product->sku,
                'quantity'          => $quantity,
                'quantity_received' => 0,
                'unit_price'        => $unitPrice,
                'unit_cost'         => data_get($item, 'unit_cost'),
                'total_price'       => $unitPrice === null ? null : $unitPrice * $quantity,
                'status'            => 'pending',
            ];
        }

        return $lines;
    }

    protected function notFound(): JsonResponse
    {
        return response()->json(['error' => 'Purchase order resource not found.'], 404);
    }
}
