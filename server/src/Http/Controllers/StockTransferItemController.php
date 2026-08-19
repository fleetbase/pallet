<?php

namespace Fleetbase\Pallet\Http\Controllers;

use Fleetbase\Pallet\Http\Resources\StockTransferItem as StockTransferItemResource;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\ProductVariant;
use Fleetbase\Pallet\Models\StockTransfer;
use Fleetbase\Pallet\Models\StockTransferItem;
use Fleetbase\Support\Http;
use Illuminate\Http\Request;

class StockTransferItemController extends PalletResourceController
{
    public $resource = 'stock-transfer-item';

    public function createRecord(Request $request)
    {
        $this->validateRequest($request);

        $data          = $request->input('stock_transfer_item');
        $transferId    = data_get($data, 'stock_transfer_uuid');
        $productId     = data_get($data, 'product_uuid');
        $variantId     = data_get($data, 'variant_uuid');
        $transfer      = $this->findTransfer($transferId);
        $product       = $this->findProduct($productId);
        $quantity      = (int) data_get($data, 'quantity', 0);

        if (!$transfer) {
            return response()->error('Selected transfer could not be found.', 422);
        }

        // items added after approval would never be shipped, and items added
        // after shipping would be received without stock ever leaving the source
        if (!in_array($transfer->status, ['pending', 'draft'], true)) {
            return response()->error("Items cannot be added to a transfer with status '{$transfer->status}'.", 422);
        }

        if (!$product) {
            return response()->error('Product is required to add a transfer item.', 422);
        }

        if ($product->has_variants && !$variantId) {
            return response()->error('Variant is required for transfer items on products with variants.', 422);
        }

        if ($quantity <= 0) {
            return response()->error('Transfer item quantity must be greater than zero.', 422);
        }

        if ($variantId) {
            $variant = ProductVariant::where('company_uuid', session('company'))
                ->where('product_uuid', $product->uuid)
                ->where(fn ($query) => $query->where('uuid', $variantId)->orWhere('public_id', $variantId))
                ->first();

            if (!$variant) {
                return response()->error('Selected variant does not belong to this product.', 422);
            }

            $variantId = $variant->uuid;
        }

        $item = new StockTransferItem([
            'company_uuid'         => session('company'),
            'stock_transfer_uuid'  => $transfer->uuid,
            'product_uuid'         => $product->uuid,
            'variant_uuid'         => $variantId,
            'quantity'             => $quantity,
            'quantity_received'    => (int) data_get($data, 'quantity_received', 0),
            'lot_number'           => data_get($data, 'lot_number'),
            'serial_number'        => data_get($data, 'serial_number'),
            'notes'                => data_get($data, 'notes'),
            'meta'                 => data_get($data, 'meta', []),
        ]);

        $item->save();

        if (Http::isInternalRequest($request)) {
            StockTransferItemResource::wrap($this->resourceSingularlName);
        }

        return new StockTransferItemResource($item);
    }

    protected function findTransfer(?string $id): ?StockTransfer
    {
        if (!$id) {
            return null;
        }

        return StockTransfer::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $id)->orWhere('public_id', $id))
            ->first();
    }

    protected function findProduct(?string $id): ?Product
    {
        if (!$id) {
            return null;
        }

        return Product::where('company_uuid', session('company'))
            ->where(fn ($query) => $query->where('uuid', $id)->orWhere('public_id', $id))
            ->first();
    }
}
