<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Storefront link uniqueness was only enforced in PHP
     * (StorefrontInventoryController::assertStorefrontProductLinkAvailable),
     * so concurrent link calls could bind one Storefront product/variant to
     * multiple Pallet records. Unique indexes are the backstop; NULLs stay
     * unrestricted (unlinked rows).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pallet_products', function (Blueprint $table) {
            $table->unique('storefront_product_uuid', 'pallet_products_storefront_product_unique');
        });

        Schema::table('pallet_product_variants', function (Blueprint $table) {
            $table->unique('storefront_variant_uuid', 'pallet_variants_storefront_variant_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pallet_products', function (Blueprint $table) {
            $table->dropUnique('pallet_products_storefront_product_unique');
        });

        Schema::table('pallet_product_variants', function (Blueprint $table) {
            $table->dropUnique('pallet_variants_storefront_variant_unique');
        });
    }
};
