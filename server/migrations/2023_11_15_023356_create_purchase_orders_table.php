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
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id')->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('created_by_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->foreignUuid('supplier_uuid')->nullable()->index()->references('uuid')->on('vendors');
            $table->foreignUuid('transaction_uuid')->nullable()->index()->references('uuid')->on('transactions');
            $table->foreignUuid('assigned_to_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->foreignUuid('point_of_contact_uuid')->nullable()->index()->references('uuid')->on('contacts');
            $table->string('reference_code')->nullable();
            $table->string('reference_url')->nullable();
            $table->mediumText('description')->nullable();
            $table->mediumText('comments')->nullable();
            $table->string('currency')->nullable();
            $table->string('status')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('order_created_at')->nullable();
            $table->timestamp('expected_delivery_at')->nullable();
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
        Schema::dropIfExists('pallet_purchase_orders');
    }
}
