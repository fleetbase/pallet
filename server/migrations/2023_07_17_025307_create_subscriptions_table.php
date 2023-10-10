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
        Schema::create('billing_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->nullable()->index();
            $table->char('company_uuid', 36)->nullable()->index();
            $table->char('payment_gateway_uuid', 36)->nullable()->index();
            $table->char('plan_uuid', 36)->nullable()->index();
            $table->string('name')->nullable();
            $table->string('payment_gateway_id')->nullable()->index();
            $table->string('payment_gateway_status')->index()->nullable();
            $table->string('payment_gateway_price')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
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
        Schema::dropIfExists('billing_subscriptions');
    }
};
