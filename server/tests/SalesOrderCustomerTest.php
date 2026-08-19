<?php

use Fleetbase\FleetOps\Models\Contact;
use Fleetbase\Pallet\Http\Controllers\SalesOrderController;
use Fleetbase\Pallet\Models\SalesOrder;
use Fleetbase\Pallet\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

function makeSalesOrderCustomerFixture(): array
{
    $company = (string) Illuminate\Support\Str::uuid();
    session(['company' => $company]);

    $warehouse = Warehouse::create(['company_uuid' => $company, 'name' => 'SO WH', 'code' => 'SOC-' . uniqid()]);
    $contact   = Contact::create(['company_uuid' => $company, 'name' => 'Acme Buyer']);

    return [$company, $warehouse, $contact];
}

test('sales orders table carries the customer columns the stack references', function () {
    expect(Schema::hasColumn('pallet_sales_orders', 'customer_uuid'))->toBeTrue()
        ->and(Schema::hasColumn('pallet_sales_orders', 'customer_type'))->toBeTrue();
});

test('a sales order can be created with a customer and resolves the relation', function () {
    [, $warehouse, $contact] = makeSalesOrderCustomerFixture();

    (new SalesOrderController())->createRecord(Request::create('/', 'POST', [
        'sales_order' => [
            'warehouse_uuid' => $warehouse->uuid,
            'customer_uuid'  => $contact->uuid,
            'customer_type'  => 'contact',
        ],
    ]));

    $salesOrder = SalesOrder::where('warehouse_uuid', $warehouse->uuid)->first();

    expect($salesOrder)->not->toBeNull()
        ->and($salesOrder->customer_uuid)->toBe($contact->uuid)
        ->and($salesOrder->customer_type)->toBe('contact')
        ->and($salesOrder->customer)->not->toBeNull()
        ->and($salesOrder->customer->name)->toBe('Acme Buyer');
});

test('sales orders remain searchable by their declared searchable columns', function () {
    [$company, $warehouse, $contact] = makeSalesOrderCustomerFixture();

    SalesOrder::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => $warehouse->uuid,
        'customer_uuid'  => $contact->uuid,
        'customer_type'  => 'contact',
        'status'         => 'pending',
    ]);

    // every column the model declares searchable must exist, or search 500s
    $searchable = (fn () => $this->searchableColumns)->call(new SalesOrder());
    $query      = SalesOrder::where('company_uuid', $company);

    foreach ($searchable as $column) {
        $query->orWhere($column, 'like', '%contact%');
    }

    expect($searchable)->toContain('customer_type')
        ->and($query->get())->not->toBeEmpty();
});
