<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pallet_products', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_products', 'storefront_product_uuid')) {
                $table->uuid('storefront_product_uuid')->nullable()->index()->after('supplier_uuid');
            }
        });

        Schema::table('pallet_product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_product_variants', 'storefront_variant_uuid')) {
                $table->uuid('storefront_variant_uuid')->nullable()->index()->after('product_uuid');
            }
        });
    }

    public function down()
    {
        Schema::table('pallet_product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('pallet_product_variants', 'storefront_variant_uuid')) {
                $table->dropColumn('storefront_variant_uuid');
            }
        });

        Schema::table('pallet_products', function (Blueprint $table) {
            if (Schema::hasColumn('pallet_products', 'storefront_product_uuid')) {
                $table->dropColumn('storefront_product_uuid');
            }
        });
    }
};
