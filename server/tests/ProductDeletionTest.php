<?php

namespace Server\tests;

use Fleetbase\Pallet\Models\Inventory;
use Fleetbase\Pallet\Models\Product;
use Illuminate\Support\Str;

/*
 * Products soft-delete, so deleting one used to leave its inventory rows behind,
 * live and still listed. The relation resolved to nothing and the inventory screen
 * rendered them as "Untitled product" — phantom stock that looked like ordinary
 * incomplete data. Both delete paths are covered here because they behave
 * differently: bulkRemove() mass-deletes through the query builder and does not
 * fire model events, and it is the path the console actually uses.
 */

function makeProduct(string $name, string $company): Product
{
    session(['company' => $company]);

    return Product::create(['name' => $name, 'company_uuid' => $company]);
}

test('deleting a product takes its empty inventory rows with it', function () {
    $company = (string) Str::uuid();
    $product = makeProduct('Empty Stock Product', $company);

    $inventory = Inventory::create([
        'company_uuid' => $company,
        'product_uuid' => $product->uuid,
        'quantity'     => 0,
    ]);

    $product->delete();

    expect(Inventory::where('uuid', $inventory->uuid)->first())->toBeNull('inventory must not outlive its product');
});

test('a product still holding stock cannot be deleted', function () {
    $company = (string) Str::uuid();
    $product = makeProduct('Stocked Product', $company);

    $inventory = Inventory::create([
        'company_uuid' => $company,
        'product_uuid' => $product->uuid,
        'quantity'     => 40,
    ]);

    expect(fn () => $product->delete())->toThrow(\Exception::class, 'still holds 40 units');

    expect(Product::where('uuid', $product->uuid)->first())->not->toBeNull('the product must survive a refused delete')
        ->and(Inventory::where('uuid', $inventory->uuid)->first())->not->toBeNull('and so must its stock');
});

test('bulk delete honours the same guard as a single delete', function () {
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $empty   = makeProduct('Bulk Empty', $company);
    $stocked = makeProduct('Bulk Stocked', $company);

    Inventory::create(['company_uuid' => $company, 'product_uuid' => $empty->uuid, 'quantity' => 0]);
    Inventory::create(['company_uuid' => $company, 'product_uuid' => $stocked->uuid, 'quantity' => 7]);

    // the console's bulk delete is the path that produced the orphans
    expect(fn () => (new Product())->bulkRemove([$empty->uuid, $stocked->uuid]))
        ->toThrow(\Exception::class, 'still holds 7 units');

    // and the transaction must leave the whole batch intact, not half-deleted
    expect(Product::where('uuid', $empty->uuid)->first())->not->toBeNull('a refused batch must not partially apply');
});

test('bulk delete removes products that hold no stock', function () {
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $first  = makeProduct('Bulk Clean One', $company);
    $second = makeProduct('Bulk Clean Two', $company);

    Inventory::create(['company_uuid' => $company, 'product_uuid' => $first->uuid, 'quantity' => 0]);

    $count = (new Product())->bulkRemove([$first->uuid, $second->uuid]);

    expect($count)->toBe(2)
        ->and(Product::where('uuid', $first->uuid)->first())->toBeNull()
        ->and(Product::where('uuid', $second->uuid)->first())->toBeNull()
        ->and(Inventory::where('product_uuid', $first->uuid)->count())->toBe(0);
});

/*
 * The first cut of the cascade only took inventory, so deleting a product left its
 * batches behind pointing at nothing — the same orphan class, one table over. The
 * Batches screen rendered them with an empty product column.
 */
test('deleting a product takes its batches and variants with it', function () {
    $company = (string) Str::uuid();
    $product = makeProduct('Batched Product', $company);

    $batch = \Fleetbase\Pallet\Models\Batch::create([
        'company_uuid' => $company,
        'product_uuid' => $product->uuid,
        'batch_number' => 'B-' . uniqid(),
        'quantity'     => 0,
    ]);

    $variant = \Fleetbase\Pallet\Models\ProductVariant::create([
        'company_uuid' => $company,
        'product_uuid' => $product->uuid,
        'name'         => 'Large',
    ]);

    $product->delete();

    expect(\Fleetbase\Pallet\Models\Batch::where('uuid', $batch->uuid)->first())->toBeNull('a batch must not outlive its product')
        ->and(\Fleetbase\Pallet\Models\ProductVariant::where('uuid', $variant->uuid)->first())->toBeNull('nor a variant');
});

/*
 * A product can read as empty on the inventory table while a batch still says it
 * holds stock; the guard has to look at both or it will delete real quantities.
 */
test('stock recorded only against a batch still blocks the delete', function () {
    $company = (string) Str::uuid();
    $product = makeProduct('Batch Only Stock', $company);

    Inventory::create(['company_uuid' => $company, 'product_uuid' => $product->uuid, 'quantity' => 0]);

    $batch = \Fleetbase\Pallet\Models\Batch::create([
        'company_uuid' => $company,
        'product_uuid' => $product->uuid,
        'batch_number' => 'B-' . uniqid(),
        'quantity'     => 100,
    ]);

    expect(fn () => $product->delete())->toThrow(\Exception::class, 'still holds 100 units');

    expect(\Fleetbase\Pallet\Models\Batch::where('uuid', $batch->uuid)->first())->not->toBeNull();
});
