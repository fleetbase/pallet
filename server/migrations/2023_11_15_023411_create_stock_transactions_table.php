<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockTransactionsTable extends Migration
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
            $table->uuid('uuid', 191)->nullable()->index();
            $table->foreignUuid('product_uuid')->nullable()->index()->references('uuid')->on('entities');
            $table->foreignUuid('destination_uuid')->nullable()->index()->references('uuid')->on('places');
            $table->uuid('source_uuid')->nullable()->index();
            $table->string('source_type')->nullabe();
            $table->string('transaction_type')->nullabe();
            $table->integer('quantity')->nullabe();
            $table->timestamp('transaction_created_at')->nullabe();
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
        Schema::dropIfExists('pallet_stock_transactions');
    }
}
