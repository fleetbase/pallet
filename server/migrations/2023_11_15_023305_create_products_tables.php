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
        Schema::create('pallet_products', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id')->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('created_by_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->foreignUuid('category_uuid')->nullable()->index()->references('uuid')->on('categories');
            $table->foreignUuid('supplier_uuid')->nullable()->index()->references('uuid')->on('vendors');
            $table->mediumText('photo_uuid')->nullable();
            $table->string('internal_id')->nullable()->index();
            $table->string('name')->nullable()->index();
            $table->mediumText('description')->nullable();
            $table->string('sku')->nullable()->index();
            $table->mediumText('barcode')->nullable();
            $table->string('currency', 3)->nullable();
            $table->decimal('unit_cost', 12, 4)->nullable();
            $table->decimal('unit_price', 12, 4)->nullable();
            $table->decimal('sale_price', 12, 4)->nullable();
            $table->decimal('declared_value', 12, 4)->nullable();
            $table->decimal('weight', 12, 4)->nullable();
            $table->string('weight_unit', 25)->nullable();
            $table->decimal('length', 12, 4)->nullable();
            $table->decimal('width', 12, 4)->nullable();
            $table->decimal('height', 12, 4)->nullable();
            $table->string('dimensions_unit', 25)->nullable();
            $table->json('dimensions')->nullable();
            $table->boolean('has_variants')->default(false);
            $table->boolean('is_serialized')->default(false);
            $table->boolean('is_lot_tracked')->default(false);
            $table->boolean('is_kit')->default(false);
            $table->boolean('is_perishable')->default(false);
            $table->boolean('requires_quality_check')->default(false);
            $table->integer('reorder_point')->nullable();
            $table->integer('reorder_quantity')->nullable();
            $table->integer('shelf_life_days')->nullable();
            $table->string('status')->nullable()->default('active')->index();
            $table->string('slug')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_uuid', 'sku']);
        });

        Schema::create('pallet_product_variants', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id')->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('created_by_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->foreignUuid('product_uuid')->index()->references('uuid')->on('pallet_products')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('sku')->nullable()->index();
            $table->mediumText('barcode')->nullable();
            $table->json('option_values')->nullable();
            $table->string('currency', 3)->nullable();
            $table->decimal('unit_cost', 12, 4)->nullable();
            $table->decimal('unit_price', 12, 4)->nullable();
            $table->decimal('sale_price', 12, 4)->nullable();
            $table->decimal('declared_value', 12, 4)->nullable();
            $table->decimal('weight', 12, 4)->nullable();
            $table->string('weight_unit', 25)->nullable();
            $table->string('status')->nullable()->default('active')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_uuid', 'sku']);
            $table->index(['product_uuid', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pallet_product_variants');
        Schema::dropIfExists('pallet_products');
    }
};
