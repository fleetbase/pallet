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
            $table->uuid('uuid')->nullable()->unique();
            $table->string('public_id')->nullable()->unique();
            $table->foreignUuid('company_uuid')->nullable()->index()->references('uuid')->on('companies');
            $table->foreignUuid('created_by_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->foreignUuid('performed_by_uuid')->nullable()->index()->references('uuid')->on('users');
            $table->uuid('auditable_uuid')->nullabe()->index();
            $table->string('auditable_type')->nullabe();
            $table->mediumText('reason')->nullabe();
            $table->mediumText('comments')->nullabe();
            $table->string('action')->nullabe();
            $table->string('type')->nullabe();
            $table->json('meta')->nullabe();
            $table->json('new_values')->nullabe();
            $table->json('old_values')->nullabe();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
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
        Schema::dropIfExists('pallet_audits');
    }
}
