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
        Schema::create('pallet_batches', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id')->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('created_by_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->string('batch_number')->nullable();
            $table->foreignUuid('product_uuid')->nullable()->index()->references('uuid')->on('pallet_products');
            $table->foreignUuid('variant_uuid')->nullable()->index()->references('uuid')->on('pallet_product_variants');
            $table->integer('quantity')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('manufacture_date_at')->nullable();
            $table->timestamp('expiry_date_at')->nullable();
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
        Schema::dropIfExists('pallet_batches');
    }
};
