<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The stock ledger was unwritable and unreadable as shipped:
     * Inventory::recordStockTransaction() inserts `transaction_date_at`
     * (also in the model's fillable/searchable columns) but the create
     * migration never added the column, so every ledger insert failed;
     * and Inventory::transactions() joins on `inventory_uuid`, which
     * did not exist either.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pallet_stock_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_stock_transactions', 'transaction_date_at')) {
                $table->timestamp('transaction_date_at')->nullable()->index()->after('quantity');
            }
            if (!Schema::hasColumn('pallet_stock_transactions', 'inventory_uuid')) {
                $table->foreignUuid('inventory_uuid')->nullable()->index()->after('batch_uuid')->references('uuid')->on('pallet_inventories');
            }
        });

        Schema::table('pallet_stock_transactions', function (Blueprint $table) {
            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pallet_stock_transactions', function (Blueprint $table) {
            $table->dropIndex(['transaction_type']);
        });

        // one column drop per call: SQLite cannot combine multiple drops
        Schema::table('pallet_stock_transactions', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['inventory_uuid']);
            }
            $table->dropColumn('inventory_uuid');
        });

        Schema::table('pallet_stock_transactions', function (Blueprint $table) {
            $table->dropColumn('transaction_date_at');
        });
    }
};
