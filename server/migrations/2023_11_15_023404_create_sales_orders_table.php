<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pallet_sales_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id')->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('created_by_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->foreignUuid('transaction_uuid')->nullable()->index()->references('uuid')->on('transactions');
            $table->foreignUuid('assigned_to_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->foreignUuid('point_of_contact_uuid')->nullable()->index()->references('uuid')->on('contacts');
            $table->foreignUuid('customer_uuid')->nullable()->index()->references('uuid')->on('contacts');
            $table->string('customer_type')->nullable();
            $table->json('meta')->nullable();
            $table->string('status')->nullable();
            $table->string('customer_reference_code')->nullable();
            $table->string('reference_code')->nullable();
            $table->string('reference_url')->nullable();
            $table->mediumText('description')->nullable();
            $table->mediumText('comments')->nullable();
            $table->timestamp('order_date_at')->nullable();
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
        Schema::dropIfExists('pallet_sales_orders');
    }
}
