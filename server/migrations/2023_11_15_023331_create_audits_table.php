<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pallet_audits', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid', 191)->nullable()->index();
            $table->foreignUuid('user_uuid', 191)->nullable()->index()->references('uuid')->on('users');
            $table->string('action')->nullabe();
            $table->string('auditable_type')->nullabe();
            $table->uuid('auditable_uuid', 191)->nullabe()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
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
        Schema::dropIfExists('pallet_audits');
    }
}
