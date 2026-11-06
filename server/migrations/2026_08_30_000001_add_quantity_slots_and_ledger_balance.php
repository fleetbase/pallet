<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Two gaps the UI design called blocking.
 *
 * G-2 — a warehouse quantity is a set, not a number. Inventory already carried
 * `quantity`, `reserved_quantity` and `available_quantity`; the three slots that
 * complete the set are stock moving between warehouses, stock on an open purchase
 * order, and stock held back from sale. Without them the inventory screens cannot
 * answer "can I promise this?" — they can only answer "what is on the shelf".
 *
 * G-1 — `balance_after` on the ledger. A stock transaction records a delta; without
 * the resulting balance a row cannot answer "why is this number what it is" without
 * replaying every prior transaction for that inventory record.
 *
 * `available_quantity` deliberately keeps its existing formula (on-hand minus
 * reserved). Subtracting `quarantined` would be defensible warehouse semantics, but it
 * changes stock maths that the reserve/commit/fulfil chain is tested against, so it is
 * a separate decision rather than a side effect of adding a column.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pallet_inventories', function (Blueprint $table) {
            foreach (['in_transit', 'on_order', 'quarantined'] as $column) {
                if (!Schema::hasColumn('pallet_inventories', $column)) {
                    $table->integer($column)->unsigned()->default(0)->after('available_quantity');
                }
            }
        });

        Schema::table('pallet_stock_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_stock_transactions', 'balance_after')) {
                // Nullable rather than defaulted: historical rows predate the column and
                // their true balance is not recoverable, so null honestly means "unknown"
                // where 0 would be a wrong number the ledger would happily display.
                $table->integer('balance_after')->nullable()->after('quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // One Schema::table call per column: SQLite rejects multiple dropColumn calls
        // in a single modification, and the test harness runs every migration down.
        foreach (['in_transit', 'on_order', 'quarantined'] as $column) {
            if (Schema::hasColumn('pallet_inventories', $column)) {
                Schema::table('pallet_inventories', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        Schema::table('pallet_stock_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('pallet_stock_transactions', 'balance_after')) {
                $table->dropColumn('balance_after');
            }
        });
    }
};
