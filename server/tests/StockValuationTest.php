<?php

use Fleetbase\Pallet\Http\Controllers\MetricsController;
use Illuminate\Support\Str;

/*
 * The dashboard's Stock Value tile read "$0" against 94 units on hand. Arithmetically
 * correct — every inventory row had a null unit_cost and COALESCE turned each into zero
 * — but a warehouse holding stock and reporting no value reads as a broken tile rather
 * than as missing cost data.
 *
 * Substituting the product's sale price would have been the wrong fix: valuation is a
 * cost figure, and quietly using price misstates a number people reconcile against
 * their books. So an unvaluable stock reports null, a partly costed one reports what it
 * can and says what it excluded, and a fully costed one reports the total.
 */
function stockValueMetricFor(array $totals): array
{
    $controller = new MetricsController();
    $method     = (new ReflectionClass($controller))->getMethod('stockValueMetric');
    $method->setAccessible(true);

    return $method->invoke($controller, (object) $totals);
}

test('stock with no unit cost anywhere reports no value rather than zero', function () {
    $metric = stockValueMetricFor(['stock_value' => 0, 'uncosted_units' => 94, 'total_units' => 94]);

    expect($metric['value'])->toBeNull()
        ->and($metric['footnote'])->toBe('No unit cost recorded on any stock');
});

test('a partly costed stock reports what it can and says what it excluded', function () {
    $metric = stockValueMetricFor(['stock_value' => 250.0, 'uncosted_units' => 44, 'total_units' => 94]);

    expect($metric['value'])->toBe(250.0)
        ->and($metric['footnote'])->toBe('Excludes 44 units with no unit cost');
});

test('a fully costed stock reports its value plainly', function () {
    $metric = stockValueMetricFor(['stock_value' => 470.0, 'uncosted_units' => 0, 'total_units' => 94]);

    expect($metric['value'])->toBe(470.0)
        ->and($metric['footnote'])->toBe('On-hand inventory value');
});

test('an empty warehouse reports zero, not unknown', function () {
    // No stock at all is a real zero: there is nothing to value, and nothing missing.
    $metric = stockValueMetricFor(['stock_value' => 0, 'uncosted_units' => 0, 'total_units' => 0]);

    expect($metric['value'])->toBe(0.0)
        ->and($metric['footnote'])->toBe('On-hand inventory value');
});
