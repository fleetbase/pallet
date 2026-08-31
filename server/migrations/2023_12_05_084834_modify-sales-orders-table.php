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
        // one column drop per call: SQLite cannot combine multiple
        // dropColumn/renameColumn operations in a single table modification
        Schema::table('pallet_sales_orders', function (Blueprint $table) {
            $table->dropColumn('customer_type');
        });

        Schema::table('pallet_sales_orders', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['customer_uuid']);
            }
            $table->dropColumn('customer_uuid');
        });

        Schema::table('pallet_sales_orders', function (Blueprint $table) {
            $table->foreignUuid('supplier_uuid')->nullable()->index()->after('point_of_contact_uuid')->references('uuid')->on('vendors');
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
            $table->string('customer_type')->nullable();
            $table->foreignUuid('customer_uuid')->nullable()->index()->after('point_of_contact_uuid')->references('uuid')->on('contacts');
        });

        Schema::table('pallet_sales_orders', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['supplier_uuid']);
            }
            $table->dropColumn('supplier_uuid');
        });
    }
};
