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
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->nullable()->index();
            $table->char('payment_gateway_uuid', 36)->nullable()->index();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('payment_gateway_id')->nullable()->index();
            $table->integer('price')->nullable();
            $table->boolean('recurring')->default(1);
            $table->string('billing_period')->default('monthly');
            $table->string('interval')->nullable();
            $table->integer('trial_period_days')->nullable();
            $table->json('options')->nullable();
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
        Schema::dropIfExists('billing_plans');
    }
};
