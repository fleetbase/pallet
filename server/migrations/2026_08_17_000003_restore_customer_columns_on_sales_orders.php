<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migration 2023_12_05_084834 dropped `customer_uuid` and `customer_type`
     * while adding `supplier_uuid`, but the rest of the stack kept treating a
     * sales order as having a customer: the model declares both columns
     * fillable (and `customer_type` searchable) with a `customer()` relation
     * eager-loaded via $with, the controller resolves and persists a Contact,
     * the API resource exposes them, and the Ember form has a customer
     * selector. So creating a sales order with a customer, and any search
     * touching the declared searchable columns, both failed at the database.
     * A sales order needs the party it is sold to; the columns come back.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pallet_sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_sales_orders', 'customer_uuid')) {
                $table->foreignUuid('customer_uuid')->nullable()->index()->after('point_of_contact_uuid')->references('uuid')->on('contacts');
            }
        });

        Schema::table('pallet_sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_sales_orders', 'customer_type')) {
                $table->string('customer_type')->nullable()->after('customer_uuid');
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
        Schema::table('pallet_sales_orders', function (Blueprint $table) {
            $table->dropColumn('customer_type');
        });

        Schema::table('pallet_sales_orders', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['customer_uuid']);
            }
            $table->dropColumn('customer_uuid');
        });
    }
};
