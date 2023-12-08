<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifySalesOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pallet_sales_orders', function (Blueprint $table) {
            $table->dropColumn('customer_type');
            $table->dropForeign(['customer_uuid']);
            $table->dropColumn('customer_uuid');
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
            
            $table->dropForeign(['supplier_uuid']);
            $table->dropColumn('supplier_uuid');
        });
    }
}
