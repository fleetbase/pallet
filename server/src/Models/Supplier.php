<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\FleetOps\Models\Vendor;
use Spatie\Activitylog\LogOptions;

/**
 * Generated from the table schema, the model's casts and its relation methods.
 * PHPStan cannot see Eloquent's magic properties without these; every one of them
 * was a reported error before.
 *
 * @property ?int                        $id
 * @property ?string                     $_key
 * @property string                      $uuid
 * @property ?string                     $public_id
 * @property ?string                     $company_uuid
 * @property ?string                     $place_uuid
 * @property ?string                     $type_uuid
 * @property ?string                     $connect_company_uuid
 * @property ?string                     $logo_uuid
 * @property ?string                     $name
 * @property ?string                     $internal_id
 * @property ?string                     $business_id
 * @property ?int                        $connected
 * @property ?string                     $email
 * @property ?string                     $phone
 * @property ?string                     $website_url
 * @property ?string                     $country
 * @property ?array                      $meta
 * @property ?array                      $callbacks
 * @property ?string                     $type
 * @property ?string                     $notes
 * @property ?string                     $status
 * @property ?string                     $slug
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property mixed                       $address
 * @property mixed                       $address_street
 * @property mixed                       $logo_url
 */
class Supplier extends Vendor
{
    /**
     * Overwrite both vendor resource name with `payloadKey`.
     *
     * @var string
     */
    protected $payloadKey = 'supplier';

    /**
     * The type of public Id to generate.
     *
     * @var string
     */
    protected $publicIdType = 'supplier';

    /**
     * Configure Spatie activity log options.
     * Logs only the specified attributes when they change (dirty only).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'email',
                'phone',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
