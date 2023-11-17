<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pallet_inventories', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id')->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('created_by_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->foreignUuid('product_uuid')->nullable()->index()->references('uuid')->on('entities');
            $table->foreignUuid('warehouse_uuid')->nullable()->index()->references('uuid')->on('places');
            $table->foreignUuid('supplier_uuid')->nullable()->index()->references('uuid')->on('vendors');
            $table->foreignUuid('batch_uuid')->nullable()->index()->references('uuid')->on('pallet_batches');
            $table->mediumText('comments')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('min_quantity')->nullable();
            $table->json('meta')->nullable();
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
        Schema::dropIfExists('pallet_inventories');
    }
}
