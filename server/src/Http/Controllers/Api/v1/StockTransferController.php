<?php

namespace Fleetbase\Pallet\Http\Controllers\Api\v1;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Pallet\Http\Requests\CreateStockTransferRequest;
use Fleetbase\Pallet\Http\Resources\v1\DeletedResource;
use Fleetbase\Pallet\Http\Resources\v1\StockTransfer as StockTransferResource;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\StockTransfer;
use Fleetbase\Pallet\Models\StockTransferItem;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Moving stock between warehouses.
 *
 * The lifecycle is exposed as transitions rather than a settable status field —
 * shipping deducts from the source, receiving credits the destination, and
 * cancelling an in-transit transfer restores what was shipped. Letting a caller
 * assign `status` directly would move the record without moving the stock.
 */
class StockTransferController extends Controller
{
    protected array $relations = ['fromWarehouse', 'toWarehouse', 'items', 'items.product', 'items.variant'];

    public function create(CreateStockTransferRequest $request)
    {
        $from = $this->resolveWarehouse($request->input('from_warehouse'));
        $to   = $this->resolveWarehouse($request->input('to_warehouse'));

        if (!$from || !$to) {
            return response()->json(['error' => 'Both source and destination warehouses must be found.'], 404);
        }

        if ($from->uuid === $to->uuid) {
            return response()->json(['error' => 'Source and destination warehouses must be different.'], 422);
        }

        $lines = $this->resolveLines($request);

        if ($lines instanceof JsonResponse) {
            return $lines;
        }

        $transfer = DB::transaction(function () use ($request, $from, $to, $lines) {
            $transfer = StockTransfer::create([
                'company_uuid'        => session('company'),
                'from_warehouse_uuid' => $from->uuid,
                'to_warehouse_uuid'   => $to->uuid,
                'type'                => $request->input('type', 'standard'),
                'status'              => 'pending',
                'notes'               => $request->input('notes'),
                'meta'                => $request->input('meta', []),
            ]);

            foreach ($lines as $line) {
                StockTransferItem::create(array_merge($line, [
                    'company_uuid'        => session('company'),
                    'stock_transfer_uuid' => $transfer->uuid,
                ]));
            }

            return $transfer;
        });

        return new StockTransferResource($transfer->fresh($this->relations));
    }

    public function query(Request $request)
    {
        return StockTransferResource::collection(
            StockTransfer::queryWithRequest($request, function ($query) {
                $query->with($this->relations);
            })
        );
    }

    public function find($id, Request $request)
    {
        try {
            $transfer = StockTransfer::findRecordOrFail($id, $this->relations);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        return new StockTransferResource($transfer);
    }

    public function delete($id, Request $request)
    {
        try {
            $transfer = StockTransfer::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        $transfer->delete();

        return new DeletedResource($transfer);
    }

    public function approve($id, Request $request)
    {
        return $this->transition($id, fn (StockTransfer $transfer) => $transfer->approve(session('user')));
    }

    public function ship($id, Request $request)
    {
        return $this->transition($id, fn (StockTransfer $transfer) => $transfer->ship());
    }

    public function receive($id, Request $request)
    {
        return $this->transition($id, fn (StockTransfer $transfer) => $transfer->receive());
    }

    public function cancel($id, Request $request)
    {
        return $this->transition($id, fn (StockTransfer $transfer) => $transfer->cancel());
    }

    /**
     * The stock movement for each transition lives on the model, where the console
     * drives it from too. A rejected transition surfaces the model's own reason.
     */
    protected function transition(string $id, callable $apply)
    {
        try {
            $transfer = StockTransfer::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->notFound();
        }

        try {
            $apply($transfer);
        } catch (\RuntimeException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        return new StockTransferResource($transfer->fresh($this->relations));
    }

    protected function resolveWarehouse(?string $publicId): ?Warehouse
    {
        if (!$publicId) {
            return null;
        }

        return Warehouse::where('company_uuid', session('company'))
            ->where('public_id', $publicId)
            ->first();
    }

    protected function resolveLines(Request $request): array|JsonResponse
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

            $lines[] = [
                'product_uuid'      => $product->uuid,
                'quantity'          => (int) data_get($item, 'quantity', 0),
                'quantity_received' => 0,
                'lot_number'        => data_get($item, 'lot_number'),
                'serial_number'     => data_get($item, 'serial_number'),
                'notes'             => data_get($item, 'notes'),
            ];
        }

        return $lines;
    }

    protected function notFound(): JsonResponse
    {
        return response()->json(['error' => 'Stock transfer resource not found.'], 404);
    }
}
