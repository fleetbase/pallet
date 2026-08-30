<?php

use Fleetbase\Pallet\Models\Inventory;
use Illuminate\Support\Str;

/**
 * Low stock and expired stock are worklists, not record sets. Both were ordered by
 * `created_at`, so the deepest shortfall and the longest-expired batch landed
 * wherever they happened to have been created — frequently page three of a list
 * whose entire purpose is "deal with the worst one first".
 */
function seedInventory(string $company, array $attributes): Inventory
{
    return Inventory::create(array_merge([
        'company_uuid' => $company,
        'product_uuid' => (string) Str::uuid(),
        'status'       => 'active',
    ], $attributes));
}

test('low stock is ordered by depth below minimum, deepest first', function () {
    $company = (string) Str::uuid();
    session(['company' => $company]);

    // Created oldest-first in the opposite order to the shortfall, so a created_at
    // sort and a shortfall sort cannot accidentally agree.
    seedInventory($company, ['quantity' => 9, 'min_quantity' => 10]);  // short by 1
    seedInventory($company, ['quantity' => 1, 'min_quantity' => 50]);  // short by 49
    seedInventory($company, ['quantity' => 5, 'min_quantity' => 15]);  // short by 10

    $rows = Inventory::where('pallet_inventories.company_uuid', $company)
        ->summarizeByProduct()
        ->havingRaw('minimum_quantity > 0 AND total_available_quantity <= minimum_quantity')
        ->orderByRaw('(minimum_quantity - total_available_quantity) DESC')
        ->get();

    $shortfalls = $rows->map(fn ($row) => (int) $row->minimum_quantity - (int) $row->total_available_quantity)->all();

    expect($shortfalls)->toBe([49, 10, 1]);
});

test('expired stock is ordered by expiry ascending, longest expired first', function () {
    $company = (string) Str::uuid();
    session(['company' => $company]);

    seedInventory($company, ['quantity' => 3, 'expiry_date_at' => now()->subDays(2)]);
    seedInventory($company, ['quantity' => 4, 'expiry_date_at' => now()->subDays(90)]);
    seedInventory($company, ['quantity' => 5, 'expiry_date_at' => now()->subDays(30)]);

    $rows = Inventory::where('pallet_inventories.company_uuid', $company)
        ->summarizeByProduct()
        ->havingRaw('latest_expiry_date_at IS NOT NULL AND latest_expiry_date_at <= ?', [now()])
        ->orderBy('latest_expiry_date_at', 'asc')
        ->get();

    $ages = $rows->map(fn ($row) => (int) round(now()->diffInDays($row->latest_expiry_date_at)))->all();

    expect($ages)->toBe([90, 30, 2]);
});

test('stock at exactly its minimum counts as short', function () {
    // The dashboard counts "at or below minimum". A screen that only showed strictly
    // below would report a count the list could not account for.
    $company = (string) Str::uuid();
    session(['company' => $company]);

    seedInventory($company, ['quantity' => 10, 'min_quantity' => 10]);

    $rows = Inventory::where('pallet_inventories.company_uuid', $company)
        ->summarizeByProduct()
        ->havingRaw('minimum_quantity > 0 AND total_available_quantity <= minimum_quantity')
        ->get();

    expect($rows)->toHaveCount(1);
});

test('stock with no minimum set is never short', function () {
    // Without the minimum_quantity > 0 guard every product with no reorder point and
    // no stock reports as low.
    $company = (string) Str::uuid();
    session(['company' => $company]);

    seedInventory($company, ['quantity' => 0, 'min_quantity' => 0]);

    $rows = Inventory::where('pallet_inventories.company_uuid', $company)
        ->summarizeByProduct()
        ->havingRaw('minimum_quantity > 0 AND total_available_quantity <= minimum_quantity')
        ->get();

    expect($rows)->toHaveCount(0);
});
