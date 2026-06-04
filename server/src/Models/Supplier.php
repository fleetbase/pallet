<?php

namespace Fleetbase\Pallet\Models;

use Fleetbase\FleetOps\Models\Vendor;
use Spatie\Activitylog\LogOptions;

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
