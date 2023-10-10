<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;
use Fleetbase\Support\Utils;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $databaseName = Utils::getFleetbaseDatabaseName();

        Schema::table('billing_subscriptions', function (Blueprint $table) use ($databaseName) {
            $table->foreign(['company_uuid'])->references(['uuid'])->on(new Expression($databaseName . '.companies'))->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign(['payment_gateway_uuid'])->references(['uuid'])->on('billing_payment_gateways')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign(['plan_uuid'])->references(['uuid'])->on('billing_plans')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('billing_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['company_uuid']);
            $table->dropForeign(['payment_gateway_uuid']);
            $table->dropForeign(['plan_uuid']);
        });
    }
};
