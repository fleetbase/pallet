<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockAdjustmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pallet_stock_adjustment', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id')->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('created_by_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->foreignUuid('product_uuid')->nullable()->index()->references('uuid')->on('entities');
            $table->foreignUuid('assignee_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->json('meta')->nullable();
            $table->string('type')->nullable();
            $table->string('reason')->nullabe(); 
            $table->string('approval_required')->nullable(); 
            $table->integer('before_quantity')->nullable();
            $table->integer('after_quantity')->nullable();
            $table->integer('quantity')->nullabe();
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
        Schema::dropIfExists('pallet_stock_adjustment');
    }
}
