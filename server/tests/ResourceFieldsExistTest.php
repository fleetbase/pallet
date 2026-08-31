<?php

use Fleetbase\Models\File;
use Fleetbase\Pallet\Http\Resources\Internal\v1\PurchaseOrder as PurchaseOrderResource;
use Fleetbase\Pallet\Http\Resources\Internal\v1\SalesOrder as SalesOrderResource;
use Fleetbase\Pallet\Models\Audit;
use Fleetbase\Pallet\Models\Product;
use Fleetbase\Pallet\Models\PurchaseOrder;
use Fleetbase\Pallet\Models\SalesOrder;
use Fleetbase\Pallet\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Six fields the API emitted that no model could supply, so each was always null. They
 * were invisible because a JsonResource forwards unknown properties to the model through
 * __get and gets null back — no error, no warning, just a field that is never populated.
 * Static analysis only found them once the resources declared which model they wrap.
 */

test('a product photo url is read from the photo file', function () {
    // pallet_products has photo_uuid and no url column. The console reads photo_url in
    // eleven places and the upload form sets it locally, so a photo appeared on upload
    // and was gone on the next load.
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $file = File::create([
        'company_uuid'      => $company,
        'uuid'              => (string) Str::uuid(),
        'disk'              => 'local',
        'path'              => 'uploads/' . Str::random(6) . '.png',
        'original_filename' => 'front.png',
        'content_type'      => 'image/png',
    ]);

    $product = Product::create([
        'company_uuid' => $company,
        'name'         => 'Photographed',
        'sku'          => 'PH-' . uniqid(),
        'photo_uuid'   => $file->uuid,
        'status'       => 'active',
    ]);

    $fresh = Product::find($product->uuid);

    expect($fresh->photo)->not->toBeNull()
        ->and($fresh->photo_url)->not->toBeNull()
        ->and($fresh->photo_url)->toBe($file->url)
        ->and($fresh->toArray())->toHaveKey('photo_url');
});

test('a product with no photo has no photo url rather than a placeholder', function () {
    // Every caller supplies its own fallback image, and they differ. Baking one in here
    // would override all of them.
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $product = Product::create([
        'company_uuid' => $company,
        'name'         => 'Bare',
        'sku'          => 'BA-' . uniqid(),
        'status'       => 'active',
    ]);

    expect(Product::find($product->uuid)->photo_url)->toBeNull();
});

test('reading photo urls costs the same number of queries however many products there are', function () {
    // photo_url is appended, so without the photo relation in $with every catalogue and
    // inventory list would lazy-load one file per row. The count is a small constant
    // rather than one: Product eager-loads both `files` (all of them) and `photo` (the
    // one singled out), and each is its own query. What matters is that neither grows
    // with the number of rows.
    $countFileQueries = function (int $products) {
        $company = (string) Str::uuid();
        session(['company' => $company]);

        foreach (range(1, $products) as $i) {
            $file = File::create([
                'company_uuid' => $company,
                'uuid'         => (string) Str::uuid(),
                'disk'         => 'local',
                'path'         => 'uploads/' . Str::random(8) . '.png',
            ]);
            Product::create([
                'company_uuid' => $company,
                'name'         => 'Product ' . $i,
                'sku'          => 'P-' . uniqid('', true),
                'photo_uuid'   => $file->uuid,
                'status'       => 'active',
            ]);
        }

        $queries = [];
        DB::listen(function ($query) use (&$queries) { $queries[] = $query->sql; });

        $loaded = Product::where('company_uuid', $company)->get();
        $urls   = $loaded->map(fn ($product) => $product->photo_url)->all();

        DB::flushQueryLog();

        expect($loaded)->toHaveCount($products)
            ->and(array_filter($urls))->toHaveCount($products);

        return count(array_filter($queries, fn ($sql) => str_contains($sql, '"files"') || str_contains($sql, '`files`')));
    };

    expect($countFileQueries(6))->toBe($countFileQueries(2));
});

test('audits and stock adjustments expose the incrementing id the resource emits as id', function () {
    // Both tables have an id column; neither model appended it, so `id` was null on
    // every row of both lists. Harmless only because the Ember serializer keys on uuid.
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $audit = Audit::create([
        'company_uuid' => $company,
        'event_type'   => 'created',
        'action'       => 'create',
    ]);

    $adjustment = StockAdjustment::create([
        'company_uuid'   => $company,
        'inventory_uuid' => (string) Str::uuid(),
        'product_uuid'   => (string) Str::uuid(),
        'type'           => 'add',
        'quantity'       => 3,
    ]);

    expect(Audit::find($audit->uuid)->incrementing_id)->toBeInt()->toBeGreaterThan(0)
        ->and(StockAdjustment::find($adjustment->uuid)->incrementing_id)->toBeInt()->toBeGreaterThan(0);
});

test('the product incrementing id costs no extra query', function () {
    // It used to re-select the id it already had, once per product.
    $company = (string) Str::uuid();
    session(['company' => $company]);

    Product::create(['company_uuid' => $company, 'name' => 'A', 'sku' => 'A-' . uniqid(), 'status' => 'active']);
    Product::create(['company_uuid' => $company, 'name' => 'B', 'sku' => 'B-' . uniqid(), 'status' => 'active']);

    $products = Product::where('company_uuid', $company)->get();

    $queries = [];
    DB::listen(function ($query) use (&$queries) { $queries[] = $query->sql; });

    $ids = $products->map(fn ($product) => $product->incrementing_id)->all();

    expect($ids)->each->toBeInt()
        ->and($queries)->toBe([]);
});

test('neither order resource emits the other order type column', function () {
    // pallet_sales_orders has customer_reference_code and no currency; pallet_purchase_orders
    // has currency and no customer_reference_code. Both internal resources emitted both,
    // so each carried one field that was always null. The public v1 resources already
    // had it right, which is what gives the correct split away.
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $salesOrder    = SalesOrder::create(['company_uuid' => $company, 'status' => 'pending']);
    $purchaseOrder = PurchaseOrder::create(['company_uuid' => $company, 'status' => 'pending']);

    $sales    = (new SalesOrderResource($salesOrder))->resolve(request());
    $purchase = (new PurchaseOrderResource($purchaseOrder))->resolve(request());

    expect($sales)->not->toHaveKey('currency')
        ->and($sales)->toHaveKey('customer_reference_code')
        ->and($purchase)->not->toHaveKey('customer_reference_code')
        ->and($purchase)->toHaveKey('currency');
});
