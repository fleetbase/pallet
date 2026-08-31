<?php

use Fleetbase\Pallet\Http\Filter\StockTransactionFilter;
use Fleetbase\Pallet\Http\Resources\Internal\v1\StockTransaction as StockTransactionResource;
use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\StockTransaction;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * The stock ledger had a repaired model, an Ember model, an adapter and a
 * serializer — but no resource, no controller and no route, so every movement it
 * recorded was unreachable. These cover the exposure end to end.
 */
function ledgerRequest(string $company, string $queryString = ''): Request
{
    $request = Request::create('/pallet/int/v1/stock-transactions' . $queryString, 'GET');
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('company', $company);

    $route = new Route(['GET'], 'pallet/int/v1/stock-transactions', ['namespace' => '\\Fleetbase\\Pallet']);
    $request->setRouteResolver(fn () => $route);

    return $request;
}

function ledgerFor(string $company, string $queryString = ''): array
{
    $request = ledgerRequest($company, $queryString);

    return (new StockTransactionFilter($request))->apply(StockTransaction::query())->get()->all();
}

function seedLedgerCompany(int $quantity = 40): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'Ledger WH', 'code' => 'LED-' . uniqid()]);
    $product   = Product::create(['company_uuid' => $company, 'name' => 'Ledger Product', 'sku' => 'LED-' . uniqid()]);

    $inventory = Inventory::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'product_uuid'   => $product->uuid,
        'quantity'       => $quantity,
        'status'         => 'active',
    ]);

    return [$company, $inventory, $product];
}

test('creating stock writes a ledger row that the api can return', function () {
    [$company, $inventory] = seedLedgerCompany(40);

    $rows = ledgerFor($company);

    expect($rows)->not->toBeEmpty()
        ->and($rows[0]->inventory_uuid)->toBe($inventory->uuid)
        ->and((int) $rows[0]->quantity)->toBe(40)
        ->and($rows[0]->transaction_type)->toBe('received');
});

test('the ledger is scoped to the authenticated company', function () {
    [$companyA] = seedLedgerCompany(10);
    [$companyB] = seedLedgerCompany(20);

    expect(ledgerFor($companyA))->toHaveCount(1)
        ->and((int) ledgerFor($companyA)[0]->quantity)->toBe(10)
        ->and((int) ledgerFor($companyB)[0]->quantity)->toBe(20);
});

test('the ledger can be narrowed to a single inventory record', function () {
    [$company, $inventory, $product] = seedLedgerCompany(15);

    $otherProduct = Product::create(['company_uuid' => $company, 'name' => 'Other', 'sku' => 'LED-' . uniqid()]);
    Inventory::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $inventory->warehouse_uuid,
        'product_uuid'   => $otherProduct->uuid,
        'quantity'       => 99,
        'status'         => 'active',
    ]);

    expect(ledgerFor($company))->toHaveCount(2)
        ->and(ledgerFor($company, '?inventory=' . $inventory->uuid))->toHaveCount(1);
});

test('stock movements accumulate in the ledger rather than replacing each other', function () {
    [$company, $inventory] = seedLedgerCompany(50);

    $inventory->deduct(20);

    $rows  = ledgerFor($company);
    $types = collect($rows)->pluck('transaction_type')->all();

    expect($rows)->toHaveCount(2)
        ->and($types)->toContain('received');
});

test('the ledger resource exposes the movement fields the client model declares', function () {
    [$company, $inventory] = seedLedgerCompany(12);

    $row = ledgerFor($company)[0];

    // The resource asks Http::isInternalRequest() with no argument, so it reads
    // the request bound in the container — exactly as it does when serving.
    $request = ledgerRequest($company);
    app()->instance('request', $request);

    $payload = (new StockTransactionResource(StockTransaction::with(['product', 'inventory'])->find($row->uuid)))
        ->toArray($request);

    foreach (['public_id', 'transaction_type', 'quantity', 'product_uuid', 'inventory_uuid', 'source_type', 'destination_uuid', 'transaction_date_at', 'meta'] as $field) {
        expect($payload)->toHaveKey($field);
    }

    expect($payload['inventory_uuid'])->toBe($inventory->uuid)
        ->and($payload['public_id'])->toStartWith('stock_txn_');
});
