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
        Schema::create('billing_payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->nullable()->index();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('callback_url')->nullable();
            $table->string('return_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->char('logo_uuid', 36)->nullable()->index('billing_payment_gateways_logo_uuid_foreign');
            $table->char('backdrop_uuid', 36)->nullable()->index('billing_payment_gateways_backdrop_uuid_foreign');
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
        Schema::dropIfExists('billing_payment_gateways');
    }
};
