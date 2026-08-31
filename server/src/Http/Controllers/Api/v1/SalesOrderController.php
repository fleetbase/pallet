<?php

namespace Fleetbase\Pallet\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\FleetOps\Models\Contact;
use Fleetbase\Pallet\Http\Controllers\SalesOrderController as InternalSalesOrderController;
use Fleetbase\Pallet\Http\Requests\CreateSalesOrderRequest;
use Fleetbase\Pallet\Http\Requests\UpdateSalesOrderRequest;
use Fleetbase\Pallet\Http\Resources\v1\DeletedResource;
use Fleetbase\Pallet\Http\Resources\v1\SalesOrder as SalesOrderResource;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\SalesOrder;
use Fleetbase\Pallet\Models\SalesOrderItem;
use Fleetbase\Pallet\Models\Supplier;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    protected array $relations = ['customer', 'supplier', 'warehouse', 'items', 'items.product', 'items.variant', 'items.warehouse'];

    protected array $writable = [
        'status',
        'customer_reference_code',
        'reference_code',
        'reference_url',
        'description',
        'comments',
        'order_date_at',
        'expected_delivery_at',
        'meta',
    ];

    public function create(CreateSalesOrderRequest $request)
    {
        $related = $this->resolveRelated($request);

        if ($related instanceof JsonResponse) {
            return $related;
        }

        $lines = $this->resolveLines($request, $related['warehouse']);

        if ($lines instanceof JsonResponse) {
            return $lines;
        }

        $order = DB::transaction(function () use ($request, $related, $lines) {
            $order = SalesOrder::create(array_merge($request->only($this->writable), [
                'company_uuid'   => session('company'),
                'customer_uuid'  => $related['customer']?->uuid,
                'customer_type'  => $related['customer'] ? Contact::class : null,
                'supplier_uuid'  => $related['supplier']?->uuid,
                'warehouse_uuid' => $related['warehouse']?->uuid,
                'status'         => $request->input('status', 'pending'),
                'order_date_at'  => $request->input('order_date_at', now()),
            ]));

            foreach ($lines as $line) {
                SalesOrderItem::create(array_merge($line, [
                    'company_uuid'     => session('company'),
                    'sales_order_uuid' => $order->uuid,
                ]));
            }

            return $order;
        });

        return new SalesOrderResource($order->fresh($this->relations));
    }

    public function update($id, UpdateSalesOrderRequest $request)
    {
        try {
            $order = SalesOrder::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $related = $this->resolveRelated($request);

        if ($related instanceof JsonResponse) {
            return $related;
        }

        $changes = $request->only($this->writable);

        if ($related['customer']) {
            $changes['customer_uuid'] = $related['customer']->uuid;
            $changes['customer_type'] = Contact::class;
        }

        if ($related['supplier']) {
            $changes['supplier_uuid'] = $related['supplier']->uuid;
        }

        if ($related['warehouse']) {
            $changes['warehouse_uuid'] = $related['warehouse']->uuid;
        }

        $order->update($changes);

        return new SalesOrderResource($order->fresh($this->relations));
    }

    public function query(Request $request)
    {
        return SalesOrderResource::collection(
            SalesOrder::queryWithRequest($request, function ($query) {
                $query->with($this->relations);
            })
        );
    }

    public function find($id, Request $request)
    {
        try {
            $order = SalesOrder::findRecordOrFail($id, $this->relations);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        return new SalesOrderResource($order);
    }

    public function delete($id, Request $request)
    {
        try {
            $order = SalesOrder::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $order->delete();

        return new DeletedResource($order);
    }

    /**
     * Fulfill lines against the order, deducting stock.
     *
     * Delegates to the console's implementation for the same reason receiving does:
     * the pre-flight stock check, the row locking and the all-or-nothing rejection
     * on insufficient stock live there, and a second copy would be a second thing to
     * keep correct. Only the line addressing and the error envelope are translated.
     */
    public function fulfill($id, Request $request)
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

        $result = (new InternalSalesOrderController())->fulfill($delegated, $id);

        if ($result instanceof JsonResponse) {
            return $this->normalizeError($result);
        }

        try {
            $order = SalesOrder::findRecordOrFail($id, $this->relations);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        return new SalesOrderResource($order);
    }

    /**
     * The console returns {"errors": [...]}; the consumable API speaks {"error": "..."}.
     */
    protected function normalizeError(JsonResponse $response): JsonResponse
    {
        $payload = $response->getData(true);
        $message = data_get($payload, 'errors.0') ?? data_get($payload, 'error') ?? 'Sales order could not be fulfilled.';

        return response()->json(['error' => $message], $response->getStatusCode());
    }

    /**
     * Resolve customer, supplier and warehouse from their public ids in one pass, so
     * a caller sending several bad references learns about the first rather than
     * discovering them one request at a time.
     */
    protected function resolveRelated(Request $request): array|JsonResponse
    {
        $resolved = ['customer' => null, 'supplier' => null, 'warehouse' => null];

        if ($request->filled('customer')) {
            $resolved['customer'] = Contact::where('company_uuid', session('company'))
                ->where('public_id', $request->input('customer'))
                ->first();

            if (!$resolved['customer']) {
                return response()->json(['error' => 'Customer not found.'], 404);
            }
        }

        if ($request->filled('supplier')) {
            $resolved['supplier'] = Supplier::where('company_uuid', session('company'))
                ->where('public_id', $request->input('supplier'))
                ->first();

            if (!$resolved['supplier']) {
                return response()->json(['error' => 'Supplier not found.'], 404);
            }
        }

        if ($request->filled('warehouse')) {
            $resolved['warehouse'] = Warehouse::where('company_uuid', session('company'))
                ->where('public_id', $request->input('warehouse'))
                ->first();

            if (!$resolved['warehouse']) {
                return response()->json(['error' => 'Warehouse not found.'], 404);
            }
        }

        return $resolved;
    }

    /**
     * A line whose product does not resolve fails the whole request — a partially
     * created order would be worse than none.
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

            $quantity  = (int) data_get($item, 'quantity', 0);
            $unitPrice = data_get($item, 'unit_price');

            $lines[] = [
                'product_uuid'       => $product->uuid,
                'warehouse_uuid'     => $lineWarehouse?->uuid,
                'sku'                => $product->sku,
                'quantity'           => $quantity,
                'quantity_fulfilled' => 0,
                'unit_price'         => $unitPrice,
                'total_price'        => $unitPrice === null ? null : $unitPrice * $quantity,
                'status'             => 'pending',
            ];
        }

        return $lines;
    }

    protected function notFound(): JsonResponse
    {
        return response()->json(['error' => 'Sales order resource not found.'], 404);
    }
}
