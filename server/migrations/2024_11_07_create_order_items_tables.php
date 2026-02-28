<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the pallet_purchase_order_items and pallet_sales_order_items tables.
 *
 * Each line item represents a single product + quantity entry on an order.
 * Line items are the foundation of the Receive PO and Fulfill SO workflows —
 * receiving a PO iterates its items to create Inventory records, and fulfilling
 * a SO iterates its items to decrement Inventory quantities.
 */
class CreateOrderItemsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
        |----------------------------------------------------------------------
        | Purchase Order Items
        |----------------------------------------------------------------------
        | Each row is one product line on a Purchase Order.
        | On PO receipt, each item creates or increments an Inventory record.
        */
        Schema::create('pallet_purchase_order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id')->nullable()->unique();

            // Parent order
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('purchase_order_uuid')->nullable()->index()->references('uuid')->on('pallet_purchase_orders');
            $table->foreignUuid('created_by_uuid')->nullable()->index()->references('uuid')->on('users');

            // Product reference — references pallet_products which extends fleetops_entities
            $table->foreignUuid('product_uuid')->nullable()->index()->references('uuid')->on('pallet_products');

            // Warehouse destination for received goods
            $table->foreignUuid('warehouse_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouses');

            // Quantities
            $table->unsignedInteger('quantity')->default(0)->comment('Ordered quantity');
            $table->unsignedInteger('quantity_received')->default(0)->comment('Quantity actually received (set on PO receipt)');

            // Pricing
            $table->string('currency', 3)->nullable();
            $table->decimal('unit_price', 12, 4)->nullable()->comment('Price per unit at time of order');
            $table->decimal('unit_cost', 12, 4)->nullable()->comment('Cost per unit at time of order');
            $table->decimal('total_price', 14, 4)->nullable()->comment('unit_price * quantity');

            // Tracking
            $table->string('unit_of_measure', 50)->nullable();
            $table->string('sku')->nullable()->index();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expiry_date')->nullable();

            // Status: pending, partial, received, cancelled
            $table->string('status', 50)->nullable()->default('pending')->index();

            $table->mediumText('notes')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('received_at')->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            $table->index(['purchase_order_uuid', 'status']);
        });

        /*
        |----------------------------------------------------------------------
        | Sales Order Items
        |----------------------------------------------------------------------
        | Each row is one product line on a Sales Order.
        | On SO fulfilment, each item decrements the matching Inventory record.
        */
        Schema::create('pallet_sales_order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id')->nullable()->unique();

            // Parent order
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('sales_order_uuid')->nullable()->index()->references('uuid')->on('pallet_sales_orders');
            $table->foreignUuid('created_by_uuid')->nullable()->index()->references('uuid')->on('users');

            // Product reference
            $table->foreignUuid('product_uuid')->nullable()->index()->references('uuid')->on('pallet_products');

            // Source warehouse / inventory record
            $table->foreignUuid('warehouse_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouses');
            $table->foreignUuid('inventory_uuid')->nullable()->index()->references('uuid')->on('pallet_inventory');

            // Quantities
            $table->unsignedInteger('quantity')->default(0)->comment('Ordered quantity');
            $table->unsignedInteger('quantity_fulfilled')->default(0)->comment('Quantity actually dispatched (set on SO fulfilment)');

            // Pricing
            $table->string('currency', 3)->nullable();
            $table->decimal('unit_price', 12, 4)->nullable()->comment('Sale price per unit');
            $table->decimal('total_price', 14, 4)->nullable()->comment('unit_price * quantity');

            // Tracking
            $table->string('unit_of_measure', 50)->nullable();
            $table->string('sku')->nullable()->index();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();

            // Status: pending, partial, fulfilled, cancelled
            $table->string('status', 50)->nullable()->default('pending')->index();

            $table->mediumText('notes')->nullable();
            $table->json('meta')->nullable();

            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            $table->index(['sales_order_uuid', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pallet_sales_order_items');
        Schema::dropIfExists('pallet_purchase_order_items');
    }
}
