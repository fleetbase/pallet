<?php

namespace Fleetbase\Pallet\Http\Resources\Internal\v1;

use Fleetbase\Http\Resources\FleetbaseResource;
use Fleetbase\Support\Http;

class CycleCountItem extends FleetbaseResource
{
    /**
     * Whether this line's expected quantity may be sent to the client.
     *
     * Blind counting is only a real control if the number is absent from the payload.
     * Hiding it in the template left it one devtools panel away from whoever was
     * counting, which defeats the point of withholding it.
     *
     * The parent resource sets `pallet.expected_visible` before serialising its items,
     * so a count's lines never query for the status they are nested under. The reveal
     * endpoint sets the same flag to true after writing its audit record. Only a
     * standalone item request falls through to asking the parent directly.
     */
    protected function expectedIsVisible($request): bool
    {
        $flag = $request->attributes->get('pallet.expected_visible');

        if ($flag !== null) {
            return (bool) $flag;
        }

        return optional($this->cycleCount)->status !== 'in_progress';
    }

    public function toArray($request)
    {
        $expectedIsVisible = $this->expectedIsVisible($request);

        return [
            'id'                => $this->when(Http::isInternalRequest(), $this->id, $this->public_id),
            'uuid'              => $this->when(Http::isInternalRequest(), $this->uuid),
            'public_id'         => $this->when(Http::isInternalRequest(), $this->public_id),
            'company_uuid'      => $this->when(Http::isInternalRequest(), $this->company_uuid),
            'cycle_count_uuid'  => $this->cycle_count_uuid,
            'product_uuid'      => $this->product_uuid,
            'variant_uuid'      => $this->variant_uuid,
            'inventory_uuid'    => $this->inventory_uuid,
            'bin_location_uuid' => $this->bin_location_uuid,
            'product'           => $this->whenLoaded('product', fn () => new Product($this->product)),
            'variant'           => $this->whenLoaded('variant', fn () => new ProductVariant($this->variant)),
            'inventory'         => $this->whenLoaded('inventory', fn () => new Inventory($this->inventory)),
            'bin_location'      => $this->whenLoaded('binLocation', fn () => new BinLocation($this->binLocation)),
            // Variance is withheld with expected, not separately: variance is counted
            // minus expected, so shipping it alongside the counted quantity would give
            // the expected figure away by subtraction.
            'expected_quantity' => $this->when($expectedIsVisible, fn () => (int) $this->expected_quantity),
            'counted_quantity'  => (int) $this->counted_quantity,
            'variance'          => $this->when($expectedIsVisible, fn () => (int) $this->variance),
            'has_discrepancy'   => $this->has_discrepancy,
            'status'            => $this->status,
            'counted_at'        => $this->counted_at,
            'counted_by_uuid'   => $this->counted_by_uuid,
            'lot_number'        => $this->lot_number,
            'serial_number'     => $this->serial_number,
            'notes'             => $this->notes,
            'meta'              => $this->meta ?? [],
            'updated_at'        => $this->updated_at,
            'created_at'        => $this->created_at,
        ];
    }
}
