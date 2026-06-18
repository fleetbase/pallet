<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pallet_purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_purchase_orders', 'warehouse_uuid')) {
                $table->foreignUuid('warehouse_uuid')->nullable()->index()->after('supplier_uuid')->references('uuid')->on('pallet_warehouses');
            }
        });

        Schema::table('pallet_sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_sales_orders', 'warehouse_uuid')) {
                $table->foreignUuid('warehouse_uuid')->nullable()->index()->after('supplier_uuid')->references('uuid')->on('pallet_warehouses');
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
        Schema::table('pallet_purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('pallet_purchase_orders', 'warehouse_uuid')) {
                $table->dropForeign(['warehouse_uuid']);
                $table->dropColumn('warehouse_uuid');
            }
        });

        Schema::table('pallet_sales_orders', function (Blueprint $table) {
            if (Schema::hasColumn('pallet_sales_orders', 'warehouse_uuid')) {
                $table->dropForeign(['warehouse_uuid']);
                $table->dropColumn('warehouse_uuid');
            }
        });
    }
};
