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
        Schema::create('pallet_stock_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id')->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('created_by_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->foreignUuid('product_uuid')->nullable()->index()->references('uuid')->on('pallet_products');
            $table->foreignUuid('variant_uuid')->nullable()->index()->references('uuid')->on('pallet_product_variants');
            $table->foreignUuid('destination_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouses');
            $table->foreignUuid('batch_uuid')->nullable()->index()->references('uuid')->on('pallet_batches');
            $table->uuid('source_uuid')->nullable()->index();
            $table->string('source_type')->nullable();
            $table->json('meta')->nullable();
            $table->string('transaction_type')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamp('transaction_created_at')->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pallet_stock_transactions');
    }
};
