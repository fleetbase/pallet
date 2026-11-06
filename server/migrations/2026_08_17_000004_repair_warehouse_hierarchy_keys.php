<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Warehouse::aisles() and Warehouse::racks() join on `warehouse_uuid`,
     * and WarehouseZone::aisles() joins on `zone_uuid` — none of which
     * existed on pallet_warehouse_aisles / pallet_warehouse_racks, so those
     * relations could never return a row. The keys are denormalized (rather
     * than walking section -> aisle -> rack) to match pallet_bin_locations,
     * which already carries warehouse_uuid and zone_uuid directly, and
     * because the layout designer needs every shape in a warehouse from one
     * query. Existing rows are backfilled through the parent chain.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pallet_warehouse_aisles', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_warehouse_aisles', 'warehouse_uuid')) {
                $table->foreignUuid('warehouse_uuid')->nullable()->index()->after('created_by_uuid')->references('uuid')->on('pallet_warehouses');
            }
        });

        Schema::table('pallet_warehouse_aisles', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_warehouse_aisles', 'zone_uuid')) {
                $table->foreignUuid('zone_uuid')->nullable()->index()->after('warehouse_uuid')->references('uuid')->on('pallet_warehouse_zones');
            }
        });

        Schema::table('pallet_warehouse_racks', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_warehouse_racks', 'warehouse_uuid')) {
                $table->foreignUuid('warehouse_uuid')->nullable()->index()->after('created_by_uuid')->references('uuid')->on('pallet_warehouses');
            }
        });

        Schema::table('pallet_warehouse_bins', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_warehouse_bins', 'warehouse_uuid')) {
                $table->foreignUuid('warehouse_uuid')->nullable()->index()->after('created_by_uuid')->references('uuid')->on('pallet_warehouses');
            }
        });

        $this->backfillWarehouseKeys();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach (['pallet_warehouse_bins', 'pallet_warehouse_racks'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                    $blueprint->dropForeign([$table . '_warehouse_uuid_foreign']);
                }
                $blueprint->dropColumn('warehouse_uuid');
            });
        }

        Schema::table('pallet_warehouse_aisles', function (Blueprint $table) {
            $table->dropColumn('zone_uuid');
        });

        Schema::table('pallet_warehouse_aisles', function (Blueprint $table) {
            $table->dropColumn('warehouse_uuid');
        });
    }

    /**
     * Derive warehouse_uuid for pre-existing rows from the parent chain
     * (section -> aisle -> rack -> bin).
     */
    private function backfillWarehouseKeys(): void
    {
        DB::table('pallet_warehouse_aisles')
            ->whereNull('warehouse_uuid')
            ->orderBy('id')
            ->chunkById(500, function ($aisles) {
                foreach ($aisles as $aisle) {
                    $warehouseUuid = DB::table('pallet_warehouse_sections')
                        ->where('uuid', $aisle->section_uuid)
                        ->value('warehouse_uuid');

                    if ($warehouseUuid) {
                        DB::table('pallet_warehouse_aisles')->where('id', $aisle->id)->update(['warehouse_uuid' => $warehouseUuid]);
                    }
                }
            });

        DB::table('pallet_warehouse_racks')
            ->whereNull('warehouse_uuid')
            ->orderBy('id')
            ->chunkById(500, function ($racks) {
                foreach ($racks as $rack) {
                    $warehouseUuid = DB::table('pallet_warehouse_aisles')
                        ->where('uuid', $rack->aisle_uuid)
                        ->value('warehouse_uuid');

                    if ($warehouseUuid) {
                        DB::table('pallet_warehouse_racks')->where('id', $rack->id)->update(['warehouse_uuid' => $warehouseUuid]);
                    }
                }
            });

        DB::table('pallet_warehouse_bins')
            ->whereNull('warehouse_uuid')
            ->orderBy('id')
            ->chunkById(500, function ($bins) {
                foreach ($bins as $bin) {
                    $warehouseUuid = DB::table('pallet_warehouse_racks')
                        ->where('uuid', $bin->rack_uuid)
                        ->value('warehouse_uuid');

                    if ($warehouseUuid) {
                        DB::table('pallet_warehouse_bins')->where('id', $bin->id)->update(['warehouse_uuid' => $warehouseUuid]);
                    }
                }
            });
    }
};
