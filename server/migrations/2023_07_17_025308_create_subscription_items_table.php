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
        Schema::create('billing_subscription_items', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->nullable()->index();
            $table->char('subscription_uuid', 36)->nullable()->index();
            $table->string('payment_gateway_id')->nullable()->index();
            $table->string('payment_gateway_product')->nullable();
            $table->string('payment_gateway_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('billing_subscription_items');
    }
};
