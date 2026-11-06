<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pallet_stock_adjustment', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_stock_adjustment', 'inventory_uuid')) {
                $table->foreignUuid('inventory_uuid')->nullable()->index()->after('variant_uuid')->references('uuid')->on('pallet_inventories');
            }

            if (!Schema::hasColumn('pallet_stock_adjustment', 'warehouse_uuid')) {
                $table->foreignUuid('warehouse_uuid')->nullable()->index()->after('inventory_uuid')->references('uuid')->on('pallet_warehouses');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // one column drop per call: SQLite cannot combine multiple column drops
        if (Schema::hasColumn('pallet_stock_adjustment', 'warehouse_uuid')) {
            Schema::table('pallet_stock_adjustment', function (Blueprint $table) {
                $table->dropForeign(['warehouse_uuid']);
                $table->dropIndex(['warehouse_uuid']);
                $table->dropColumn('warehouse_uuid');
            });
        }

        if (Schema::hasColumn('pallet_stock_adjustment', 'inventory_uuid')) {
            Schema::table('pallet_stock_adjustment', function (Blueprint $table) {
                $table->dropForeign(['inventory_uuid']);
                $table->dropIndex(['inventory_uuid']);
                $table->dropColumn('inventory_uuid');
            });
        }
    }
};
