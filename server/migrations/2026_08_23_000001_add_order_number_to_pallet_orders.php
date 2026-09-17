<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Purchase and sales orders had no order number of their own. The resource
 * synthesised one with `'order_number' => $this->public_id`, so every order
 * printed the same string in two adjacent fields on its detail panel.
 *
 * Every other numbered record in the module carries a real series — transfers
 * TR-, waves WAVE-, cycle counts CC-, pick lists PL- — generated on create and
 * stored. Orders now do the same with PO- and SO-.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pallet_purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_purchase_orders', 'order_number')) {
                $table->string('order_number', 100)->nullable()->after('public_id');
            }
        });

        Schema::table('pallet_sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pallet_sales_orders', 'order_number')) {
                $table->string('order_number', 100)->nullable()->after('public_id');
            }
        });

        // Existing orders predate the series, so give them one rather than
        // leaving the column null on every historical record.
        $this->backfill('pallet_purchase_orders', 'PO-');
        $this->backfill('pallet_sales_orders', 'SO-');

        // Unique only after the backfill — the column is nullable and indexed
        // rather than unique-on-create so a partially migrated table cannot
        // collide on NULL under MySQL's treatment of unique nulls.
        $this->addUniqueIndex('pallet_purchase_orders', 'pallet_purchase_orders_order_number_unique');
        $this->addUniqueIndex('pallet_sales_orders', 'pallet_sales_orders_order_number_unique');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach (['pallet_purchase_orders' => 'pallet_purchase_orders_order_number_unique', 'pallet_sales_orders' => 'pallet_sales_orders_order_number_unique'] as $table => $index) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $index) {
                if (Schema::hasColumn($table, 'order_number')) {
                    if ($this->hasIndex($table, $index)) {
                        $blueprint->dropUnique($index);
                    }

                    $blueprint->dropColumn('order_number');
                }
            });
        }
    }

    /**
     * Give every existing row a number in the new series.
     */
    private function backfill(string $table, string $prefix): void
    {
        if (!Schema::hasColumn($table, 'order_number')) {
            return;
        }

        DB::table($table)
            ->whereNull('order_number')
            ->orderBy('uuid')
            ->select('uuid')
            ->chunk(200, function ($rows) use ($table, $prefix) {
                foreach ($rows as $row) {
                    DB::table($table)
                        ->where('uuid', $row->uuid)
                        ->update(['order_number' => $prefix . strtoupper(uniqid())]);
                }
            });
    }

    private function addUniqueIndex(string $table, string $index): void
    {
        if (!Schema::hasColumn($table, 'order_number') || $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index) {
            $blueprint->unique('order_number', $index);
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        try {
            return Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->introspectTable($table)
                ->hasIndex($index);
        } catch (Throwable $e) {
            // SQLite in the test harness has no doctrine introspection for some
            // index shapes; treating it as absent is safe because both callers
            // are guarded and adding a duplicate index would throw loudly.
            return false;
        }
    }
};
