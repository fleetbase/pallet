<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePalletWarehouseAisles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pallet_warehouse_aisles', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id')->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('created_by_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->foreignUuid('section_uuid')->nullable()->index()->references('uuid')->on('pallet_warehouse_sections');
            $table->string('aisle_number')->nullable();
            $table->polygon('area')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('pallet_warehouse_aisles');
    }
}
