<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the pallet_audits table.
 *
 * This table serves as the immutable operational audit trail for the Pallet
 * WMS module. It is distinct from the Spatie activity_log table, which records
 * low-level model attribute changes automatically.
 *
 * The pallet_audits table records high-level, intentional warehouse business
 * events such as stock adjustments, cycle count completions, purchase order
 * receipts, sales order fulfilments, and stock transfers. Each entry is written
 * programmatically by the system when a significant operational event occurs —
 * it is never created, edited, or deleted directly by a user via the API.
 *
 * Key concepts:
 *   - `event_type`      : Machine-readable category key for filtering
 *                         e.g. stock_adjustment | cycle_count | po_received |
 *                              so_fulfilled | stock_transfer | manual
 *   - `action`          : Human-readable label for the event row
 *                         e.g. "Stock Adjusted", "Cycle Count Completed"
 *   - `type`            : Secondary classification within an event_type
 *                         e.g. for stock_adjustment: add | remove | correction | damage | expiry
 *   - `reason`          : Reason code or user-supplied explanation for the event
 *   - `auditable_uuid`  : UUID of the primary subject model (polymorphic)
 *   - `auditable_type`  : Fully-qualified class name of the subject model
 *   - `old_values`      : Snapshot of relevant values before the event (optional)
 *   - `new_values`      : Snapshot of relevant values after the event (optional)
 *   - `meta`            : Arbitrary structured context data for the event
 *   - `scheduled_at`    : When the event was scheduled (for planned operations)
 *   - `completed_at`    : When the event was completed
 */
return new class extends Migration
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
            $table->string('uuid', 191)->nullable()->unique();
            $table->string('public_id', 191)->nullable()->unique()->index();
            $table->string('company_uuid', 191)->nullable()->index();
            $table->string('created_by_uuid', 191)->nullable()->index();
            $table->string('performed_by_uuid', 191)->nullable()->index();

            // Polymorphic subject: the primary model this event relates to
            $table->string('auditable_uuid', 191)->nullable()->index();
            $table->string('auditable_type', 191)->nullable();

            // Machine-readable event category for filtering and grouping
            $table->string('event_type', 100)->nullable()->index();

            // Human-readable label for the event
            $table->string('action', 191)->nullable()->index();

            // Secondary classification within an event_type
            $table->string('type', 100)->nullable()->index();

            // Reason code or user-supplied explanation
            $table->mediumText('reason')->nullable();

            // Free-form notes attached to the event
            $table->mediumText('comments')->nullable();

            // State snapshots before and after the event
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Arbitrary structured context data
            $table->json('meta')->nullable();

            // Timestamps for scheduled and completed events
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('company_uuid')->references('uuid')->on('companies')->onDelete('cascade');
            $table->foreign('created_by_uuid')->references('uuid')->on('users')->onDelete('set null');
            $table->foreign('performed_by_uuid')->references('uuid')->on('users')->onDelete('set null');

            // Composite indexes for common query patterns
            $table->index(['company_uuid', 'event_type', 'created_at']);
            $table->index(['auditable_uuid', 'auditable_type']);
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
};
