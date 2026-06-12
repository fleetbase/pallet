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
        // Product Kit Components
        Schema::create('pallet_product_kit_components', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id', 191)->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('kit_product_uuid')->nullable()->index()->references('uuid')->on('pallet_products')->onDelete('cascade');
            $table->foreignUuid('component_product_uuid')->nullable()->index()->references('uuid')->on('pallet_products')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

        });

        // Inventory Reservations
        Schema::create('pallet_inventory_reservations', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id', 191)->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('product_uuid')->nullable()->index()->references('uuid')->on('pallet_products');
            $table->foreignUuid('variant_uuid')->nullable()->index()->references('uuid')->on('pallet_product_variants');
            $table->foreignUuid('inventory_uuid')->nullable()->index()->references('uuid')->on('pallet_inventories');
            $table->foreignUuid('warehouse_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouses');
            $table->string('order_uuid', 191)->nullable()->index();
            $table->foreignUuid('sales_order_uuid')->nullable()->index()->references('uuid')->on('pallet_sales_orders');
            $table->foreignUuid('pick_list_uuid')->nullable()->index();
            $table->integer('quantity')->default(0);
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->string('status', 50)->default('active');
            $table->string('type', 50)->default('soft');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'expires_at']);
        });

        // Warehouse Zones
        Schema::create('pallet_warehouse_zones', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id', 191)->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('warehouse_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouses')->onDelete('cascade');
            $table->string('name', 191);
            $table->string('code', 50)->nullable();
            $table->string('type', 50)->default('general');
            $table->string('status', 50)->default('active');
            $table->boolean('temperature_controlled')->default(false);
            $table->json('temperature_range')->nullable();
            $table->decimal('capacity', 10, 2)->nullable();
            $table->decimal('current_utilization', 10, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

        });

        // Bin Locations
        Schema::create('pallet_bin_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id', 191)->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('warehouse_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouses')->onDelete('cascade');
            $table->foreignUuid('zone_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouse_zones')->onDelete('set null');
            $table->foreignUuid('aisle_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouse_aisles')->onDelete('set null');
            $table->foreignUuid('rack_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouse_racks')->onDelete('set null');
            $table->foreignUuid('section_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouse_sections')->onDelete('set null');
            $table->string('bin_number', 100);
            $table->string('barcode', 100)->nullable();
            $table->string('type', 50)->default('standard');
            $table->string('status', 50)->default('active');
            $table->decimal('capacity', 10, 2)->nullable();
            $table->decimal('current_volume', 10, 2)->default(0);
            $table->json('dimensions')->nullable();
            $table->boolean('is_pickable')->default(true);
            $table->boolean('is_replenishable')->default(true);
            $table->integer('priority')->default(5);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_uuid', 'bin_number']);
        });

        // Waves
        Schema::create('pallet_waves', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id', 191)->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('warehouse_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouses')->onDelete('cascade');
            $table->string('wave_number', 100)->unique();
            $table->string('type', 50)->default('standard');
            $table->string('status', 50)->default('pending');
            $table->integer('priority')->default(5);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_at']);
        });

        // Pick Lists
        Schema::create('pallet_pick_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id', 191)->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('warehouse_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouses')->onDelete('cascade');
            $table->foreignUuid('sales_order_uuid')->nullable()->index()->references('uuid')->on('pallet_sales_orders')->onDelete('set null');
            $table->foreignUuid('wave_uuid')->nullable()->index()->references('uuid')->on('pallet_waves')->onDelete('set null');
            $table->foreignUuid('assigned_to_uuid')->nullable()->index()->references('uuid')->on('users')->onDelete('set null');
            $table->string('pick_list_number', 100)->unique();
            $table->string('type', 50)->default('discrete');
            $table->integer('priority')->default(5);
            $table->string('status', 50)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'priority']);
        });

        // Pick List Items
        Schema::create('pallet_pick_list_items', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id', 191)->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('pick_list_uuid')->nullable()->index()->references('uuid')->on('pallet_pick_lists')->onDelete('cascade');
            $table->foreignUuid('product_uuid')->nullable()->index()->references('uuid')->on('pallet_products');
            $table->foreignUuid('variant_uuid')->nullable()->index()->references('uuid')->on('pallet_product_variants');
            $table->foreignUuid('inventory_uuid')->nullable()->index()->references('uuid')->on('pallet_inventories');
            $table->foreignUuid('bin_location_uuid')->nullable()->index()->references('uuid')->on('pallet_bin_locations')->onDelete('set null');
            $table->foreignUuid('sales_order_item_uuid')->nullable()->index();
            $table->integer('quantity_requested')->default(0);
            $table->integer('quantity_picked')->default(0);
            $table->integer('sequence_number')->default(0);
            $table->string('status', 50)->default('pending');
            $table->timestamp('picked_at')->nullable();
            $table->foreignUuid('picked_by_uuid')->nullable()->index()->references('uuid')->on('users')->onDelete('set null');
            $table->string('lot_number', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

        });

        // Cycle Counts
        Schema::create('pallet_cycle_counts', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id', 191)->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('warehouse_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouses')->onDelete('cascade');
            $table->foreignUuid('zone_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouse_zones')->onDelete('set null');
            $table->foreignUuid('assigned_to_uuid')->nullable()->index()->references('uuid')->on('users')->onDelete('set null');
            $table->string('count_number', 100)->unique();
            $table->string('type', 50)->default('standard');
            $table->string('status', 50)->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_at']);
        });

        // Cycle Count Items
        Schema::create('pallet_cycle_count_items', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id', 191)->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('cycle_count_uuid')->nullable()->index()->references('uuid')->on('pallet_cycle_counts')->onDelete('cascade');
            $table->foreignUuid('product_uuid')->nullable()->index()->references('uuid')->on('pallet_products');
            $table->foreignUuid('variant_uuid')->nullable()->index()->references('uuid')->on('pallet_product_variants');
            $table->foreignUuid('inventory_uuid')->nullable()->index()->references('uuid')->on('pallet_inventories');
            $table->foreignUuid('bin_location_uuid')->nullable()->index()->references('uuid')->on('pallet_bin_locations')->onDelete('set null');
            $table->integer('expected_quantity')->default(0);
            $table->integer('counted_quantity')->default(0);
            $table->integer('variance')->default(0);
            $table->string('status', 50)->default('pending');
            $table->timestamp('counted_at')->nullable();
            $table->foreignUuid('counted_by_uuid')->nullable()->index()->references('uuid')->on('users')->onDelete('set null');
            $table->string('lot_number', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

        });

        // Stock Transfers
        Schema::create('pallet_stock_transfers', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id', 191)->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('from_warehouse_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouses')->onDelete('cascade');
            $table->foreignUuid('to_warehouse_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouses')->onDelete('cascade');
            $table->string('transfer_number', 100)->unique();
            $table->string('status', 50)->default('pending');
            $table->string('type', 50)->default('standard');
            $table->foreignUuid('requested_by_uuid')->nullable()->index()->references('uuid')->on('users')->onDelete('set null');
            $table->foreignUuid('approved_by_uuid')->nullable()->index()->references('uuid')->on('users')->onDelete('set null');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });

        // Stock Transfer Items
        Schema::create('pallet_stock_transfer_items', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id', 191)->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('stock_transfer_uuid')->nullable()->index()->references('uuid')->on('pallet_stock_transfers')->onDelete('cascade');
            $table->foreignUuid('product_uuid')->nullable()->index()->references('uuid')->on('pallet_products');
            $table->foreignUuid('variant_uuid')->nullable()->index()->references('uuid')->on('pallet_product_variants');
            $table->integer('quantity')->default(0);
            $table->integer('quantity_received')->nullable();
            $table->string('lot_number', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

        });

        // Add new columns to existing pallet_inventories table
        Schema::table('pallet_inventories', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_inventories', 'bin_location_uuid')) {
                $table->string('bin_location_uuid', 191)->nullable()->index()->after('warehouse_uuid');
            }
            if (!Schema::hasColumn('pallet_inventories', 'zone_uuid')) {
                $table->string('zone_uuid', 191)->nullable()->index()->after('bin_location_uuid');
            }
            if (!Schema::hasColumn('pallet_inventories', 'lot_number')) {
                $table->string('lot_number', 100)->nullable()->index()->after('batch_uuid');
            }
            if (!Schema::hasColumn('pallet_inventories', 'serial_number')) {
                $table->string('serial_number', 100)->nullable()->index()->after('lot_number');
            }
            if (!Schema::hasColumn('pallet_inventories', 'uom')) {
                $table->string('uom', 50)->nullable()->after('serial_number');
            }
            if (!Schema::hasColumn('pallet_inventories', 'reserved_quantity')) {
                $table->integer('reserved_quantity')->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('pallet_inventories', 'available_quantity')) {
                $table->integer('available_quantity')->nullable()->after('reserved_quantity');
            }
            if (!Schema::hasColumn('pallet_inventories', 'max_quantity')) {
                $table->integer('max_quantity')->nullable()->after('min_quantity');
            }
            if (!Schema::hasColumn('pallet_inventories', 'reorder_point')) {
                $table->integer('reorder_point')->nullable()->after('max_quantity');
            }
            if (!Schema::hasColumn('pallet_inventories', 'unit_cost')) {
                $table->decimal('unit_cost', 10, 2)->nullable()->after('reorder_point');
            }
            if (!Schema::hasColumn('pallet_inventories', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('expiry_date_at');
            }
            if (!Schema::hasColumn('pallet_inventories', 'last_counted_at')) {
                $table->timestamp('last_counted_at')->nullable()->after('received_at');
            }
        });

        // Add new columns to places table for warehouse enhancements
        Schema::table('places', function (Blueprint $table) {
            if (!Schema::hasColumn('places', 'capacity')) {
                $table->decimal('capacity', 10, 2)->nullable()->after('name');
            }
            if (!Schema::hasColumn('places', 'current_utilization')) {
                $table->decimal('current_utilization', 10, 2)->default(0)->after('capacity');
            }
            if (!Schema::hasColumn('places', 'is_3pl')) {
                $table->boolean('is_3pl')->default(false)->after('current_utilization');
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
        Schema::dropIfExists('pallet_stock_transfer_items');
        Schema::dropIfExists('pallet_stock_transfers');
        Schema::dropIfExists('pallet_cycle_count_items');
        Schema::dropIfExists('pallet_cycle_counts');
        Schema::dropIfExists('pallet_pick_list_items');
        Schema::dropIfExists('pallet_pick_lists');
        Schema::dropIfExists('pallet_waves');
        Schema::dropIfExists('pallet_bin_locations');
        Schema::dropIfExists('pallet_warehouse_zones');
        Schema::dropIfExists('pallet_inventory_reservations');
        Schema::dropIfExists('pallet_product_kit_components');

        // Remove added columns from pallet_inventories
        Schema::table('pallet_inventories', function (Blueprint $table) {
            $table->dropColumn([
                'bin_location_uuid',
                'zone_uuid',
                'lot_number',
                'serial_number',
                'uom',
                'reserved_quantity',
                'available_quantity',
                'max_quantity',
                'reorder_point',
                'unit_cost',
                'received_at',
                'last_counted_at',
            ]);
        });

        // Remove added columns from places
        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn([
                'capacity',
                'current_utilization',
                'is_3pl',
            ]);
        });
    }
};
