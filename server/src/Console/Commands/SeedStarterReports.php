<?php

namespace Fleetbase\Pallet\Console\Commands;

use Fleetbase\Models\Company;
use Fleetbase\Models\Report;
use Fleetbase\Support\Reporting\ReportSchemaRegistry;
use Illuminate\Console\Command;

/**
 * Creates SCREENS.md §G's starter reports.
 *
 * §G is explicit that Pallet's contribution to reporting is starter reports rather than
 * builder UI — "must never: build a second query UI". These are ordinary saved reports
 * against the shared ReportBuilder, so they open, edit and execute exactly like one a
 * user built.
 *
 * The query config is **assembled from the schema registry rather than written out**.
 * A saved config embeds the whole table definition — every column with its type, label
 * and capability flags — so hand-writing six of them would duplicate that metadata six
 * times and let it drift the moment PalletReportSchema changes. Reading the registry
 * means a starter report cannot describe a column that does not exist.
 *
 * Idempotent by title within a company, so running it twice does not produce duplicates
 * and an existing report is never overwritten — someone may have edited theirs.
 */
class SeedStarterReports extends Command
{
    protected $signature = 'pallet:seed-starter-reports
                            {--company= : Limit to one company uuid, otherwise every company}
                            {--dry-run : List what would be created without writing}';

    protected $description = 'Create the starter inventory reports for the report builder';

    /**
     * The six reports §G names, each as a table and the columns it needs.
     *
     * Deliberately simple column selections rather than pre-built aggregates: the
     * builder's group-by and aggregate controls are right there, and a starter report is
     * a place to begin from, not a finished answer someone has to unpick.
     */
    protected function definitions(): array
    {
        return [
            [
                'title'       => 'Stock on hand by warehouse',
                'description' => 'Units on hand, available and reserved, to group by warehouse.',
                'table'       => 'pallet_inventories',
                'columns'     => ['quantity', 'available_quantity', 'reserved_quantity', 'status'],
            ],
            [
                'title'       => 'Stock valuation',
                'description' => 'On-hand quantity beside batch and status, to value against unit cost.',
                'table'       => 'pallet_inventories',
                'columns'     => ['quantity', 'unit_cost', 'lot_number', 'status'],
            ],
            [
                'title'       => 'Movement by product',
                'description' => 'Every recorded stock movement with its type, quantity and resulting balance.',
                'table'       => 'pallet_stock_transactions',
                'columns'     => ['transaction_type', 'quantity', 'balance_after', 'transaction_date_at'],
            ],
            [
                'title'       => 'Receipt accuracy by supplier',
                'description' => 'Purchase orders with their status and dates, to compare expected against actual receipt.',
                'table'       => 'pallet_purchase_orders',
                'columns'     => ['order_number', 'status', 'order_created_at', 'expected_delivery_at'],
            ],
            [
                'title'       => 'Count variance history',
                'description' => 'Cycle counts over time with their type, status and completion.',
                'table'       => 'pallet_cycle_counts',
                'columns'     => ['count_number', 'type', 'status', 'completed_at'],
            ],
            [
                'title'       => 'Expiry exposure',
                'description' => 'Stock carrying an expiry date, soonest first.',
                'table'       => 'pallet_inventories',
                'columns'     => ['expiry_date_at', 'quantity', 'lot_number', 'status'],
            ],
        ];
    }

    public function handle(ReportSchemaRegistry $registry)
    {
        $companies = $this->option('company')
            ? Company::where('uuid', $this->option('company'))->get()
            : Company::all();

        if ($companies->isEmpty()) {
            $this->warn('No companies found; nothing to seed.');

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($companies as $company) {
            foreach ($this->definitions() as $definition) {
                $exists = Report::where('company_uuid', $company->uuid)
                    ->where('title', $definition['title'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $config = $this->buildQueryConfig($registry, $definition);

                if ($config === null) {
                    $this->warn("Skipped '{$definition['title']}': table {$definition['table']} is not registered.");
                    $skipped++;
                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line("Would create '{$definition['title']}' for {$company->uuid}");
                    $created++;
                    continue;
                }

                Report::create([
                    'company_uuid' => $company->uuid,
                    'title'        => $definition['title'],
                    'description'  => $definition['description'],
                    'type'         => 'pallet',
                    'query_config' => $config,
                ]);

                $created++;
            }
        }

        $this->info("Starter reports: {$created} created, {$skipped} already present or unavailable.");

        return self::SUCCESS;
    }

    /**
     * Build the saved query config for one definition, from the registry.
     *
     * The shape mirrors exactly what the builder writes when a user saves — table
     * schema, selected columns, and the empty joins/conditions/sort/group the builder
     * always includes — so a starter report is indistinguishable from a hand-built one
     * and opens in the builder without special handling.
     */
    protected function buildQueryConfig(ReportSchemaRegistry $registry, array $definition): ?array
    {
        if (!$registry->hasTable($definition['table'])) {
            return null;
        }

        // getTable()->toArray(), not getTableSchema(). The latter wraps the table in
        // {table, columns, relationships, auto_join_columns}, which has no `name` or
        // `label` at the top level — saving that shape left the builder's data source
        // trigger blank even though the columns resolved. toArray() is what the builder
        // itself stores.
        $schema  = $registry->getTable($definition['table'])->toArray();
        $columns = collect($schema['columns'] ?? []);

        $selected = collect($definition['columns'])
            ->map(fn ($name) => $columns->firstWhere('name', $name))
            ->filter()
            // The builder stores an `alias` on a selected column that the table schema
            // itself does not carry; without it the column select renders blank.
            ->map(fn ($column) => array_merge($column, ['alias' => null]))
            ->values()
            ->all();

        if (empty($selected)) {
            return null;
        }

        return [
            'table'            => $schema,
            'columns'          => $selected,
            'joins'            => [],
            'conditions'       => [],
            'groupBy'          => [],
            'sortBy'           => [],
            'computed_columns' => [],
            'limit'            => 50,
        ];
    }
}
