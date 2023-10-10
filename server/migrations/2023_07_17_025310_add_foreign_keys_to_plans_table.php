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

        Schema::table('billing_payment_gateways', function (Blueprint $table) use ($databaseName) {
            $table->foreign(['backdrop_uuid'])->references(['uuid'])->on(new Expression($databaseName . '.files'))->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign(['logo_uuid'])->references(['uuid'])->on(new Expression($databaseName . '.files'))->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('billing_payment_gateways', function (Blueprint $table) {
            $table->dropForeign(['backdrop_uuid']);
            $table->dropForeign(['logo_uuid']);
        });
    }
};
