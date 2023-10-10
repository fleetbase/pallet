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

        Schema::table('billing_customers', function (Blueprint $table) use ($databaseName) {
            $table->foreign(['company_uuid'])->references(['uuid'])->on(new Expression($databaseName . '.companies'))->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('billing_customers', function (Blueprint $table) {
            $table->dropForeign(['company_uuid']);
        });
    }
};
