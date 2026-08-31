<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                        $id
 * @property string                      $uuid
 * @property ?string                     $public_id
 * @property ?string                     $company_uuid
 * @property ?string                     $kit_product_uuid
 * @property ?string                     $component_product_uuid
 * @property ?int                        $quantity
 * @property ?int                        $sort_order
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property Product|null                $componentProduct
 * @property Product|null                $kitProduct
 */
class ProductKitComponent extends Model
{
    use HasUuid;
    use HasPublicId;
    use HasApiModelBehavior;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pallet_product_kit_components';

    /**
     * Overwrite both entity resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'product_kit_component';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'kit_component';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_uuid',
        'kit_product_uuid',
        'component_product_uuid',
        'quantity',
        'sort_order',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity'   => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Relationships to eager load.
     *
     * @var array<int, string>
     */
    protected $with = ['componentProduct'];

    /**
     * Get the kit product.
     */
    public function kitProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'kit_product_uuid', 'uuid');
    }

    /**
     * Get the component product.
     */
    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_uuid', 'uuid');
    }
}
