<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pallet_purchase_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignUuid('supplier_uuid')->nullable()->index()->references('uuid')->on('vendors');
            $table->string('status')->nullable();
            $table->timestamp('order_created_at')->nullable();
            $table->timestamp('expected_delivery_at')->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pallet_purchase_orders');
    }
}
