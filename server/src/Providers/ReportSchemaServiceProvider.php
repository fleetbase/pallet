<?php

namespace Fleetbase\Pallet\Providers;

use Fleetbase\Pallet\Support\Reporting\PalletReportSchema;
use Fleetbase\Support\Reporting\ReportSchemaRegistry;
use Illuminate\Support\ServiceProvider;

class ReportSchemaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the Pallet report schema
        $this->callAfterResolving(ReportSchemaRegistry::class, function (ReportSchemaRegistry $registry) {
            $schema = new PalletReportSchema();
            $schema->registerReportSchema($registry);
        });
    }
}
