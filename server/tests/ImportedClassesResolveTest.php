<?php

use Fleetbase\FleetOps\Models\Contact;
use Fleetbase\Pallet\Models\SalesOrder;

/*
 * The public sales order API imported `Fleetbase\Models\Contact`, which does not exist —
 * Contact lives in the FleetOps package, and every other file in Pallet says so. Two
 * different failures came out of the one wrong import:
 *
 *   - `Contact::where(...)` resolving a customer threw "Class not found" at runtime, so
 *     creating a sales order with a customer through the public API died.
 *   - `Contact::class` did NOT throw. `::class` is resolved by the compiler as a literal
 *     string, so it silently wrote a `customer_type` no other code in the system uses,
 *     and the record's customer could never be resolved back.
 *
 * The second is why this went unnoticed: a wrong class name only announces itself if
 * something actually instantiates it.
 */
test('every class imported across the server resolves', function () {
    $unresolvable = [];
    $checked      = 0;

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../src'));

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        preg_match_all('/^use\s+([A-Za-z0-9_\\\\]+)\s*;/m', file_get_contents($file->getPathname()), $imports);

        foreach ($imports[1] as $class) {
            ++$checked;

            if (!class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
                $unresolvable[] = basename($file->getPathname()) . ' imports ' . $class;
            }
        }
    }

    expect($checked)->toBeGreaterThan(500)
        ->and(array_values(array_unique($unresolvable)))->toBe([]);
});

test('the customer type written by the api is the class the model reads back', function () {
    // `::class` on a misspelled class is silent, so the only way to catch a divergence
    // here is to compare what the writer stores against what the relation resolves.
    $controller = file_get_contents(__DIR__ . '/../src/Http/Controllers/Api/v1/SalesOrderController.php');

    preg_match('/^use\s+([A-Za-z0-9_\\\\]+Contact)\s*;/m', $controller, $imported);

    expect($imported[1] ?? null)->toBe(Contact::class)
        ->and(class_exists(Contact::class))->toBeTrue()
        ->and((new SalesOrder())->customer()->getRelated())->toBeInstanceOf(Contact::class);
});
