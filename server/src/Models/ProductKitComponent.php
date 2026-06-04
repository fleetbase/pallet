<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\Models\Model;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\HasUuid;

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
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'kit_component';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
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
     * @var array
     */
    protected $casts = [
        'quantity'   => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Relationships to eager load.
     *
     * @var array
     */
    protected $with = ['componentProduct'];

    /**
     * Get the kit product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function kitProduct()
    {
        return $this->belongsTo(Product::class, 'kit_product_uuid', 'uuid');
    }

    /**
     * Get the component product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function componentProduct()
    {
        return $this->belongsTo(Product::class, 'component_product_uuid', 'uuid');
    }
}
