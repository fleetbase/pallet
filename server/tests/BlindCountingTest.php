<?php

use Fleetbase\Pallet\Models\Audit;
use Fleetbase\Pallet\Models\CycleCount;
use Fleetbase\Pallet\Models\CycleCountItem;
use Fleetbase\Pallet\Http\Resources\Internal\v1\CycleCountItem as CycleCountItemResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/*
 * Blind counting is only a real control if the expected quantity is absent from the
 * payload. Hiding it in the template left it one devtools panel away from the person
 * counting, which defeats the point of withholding it.
 *
 * Variance is withheld with it, not separately: variance is counted minus expected, so
 * shipping variance beside the counted quantity gives expected away by subtraction.
 */
function makeCount(string $status): CycleCount
{
    $company = (string) Str::uuid();
    session(['company' => $company]);

    $count = CycleCount::create([
        'company_uuid'   => $company,
        'warehouse_uuid' => (string) Str::uuid(),
        'status'         => $status,
    ]);

    CycleCountItem::create([
        'company_uuid'      => $company,
        'cycle_count_uuid'  => $count->uuid,
        'product_uuid'      => (string) Str::uuid(),
        'expected_quantity' => 60,
        'counted_quantity'  => 58,
        'variance'          => -2,
        'status'            => 'counted',
    ]);

    return $count->fresh(['items']);
}

function serializeItem(CycleCountItem $item, ?bool $visible = null): array
{
    $request = Request::create('/');

    if ($visible !== null) {
        $request->attributes->set('pallet.expected_visible', $visible);
    }

    return (new CycleCountItemResource($item))->resolve($request);
}

test('an open count does not send expected quantity or variance', function () {
    $count = makeCount('in_progress');
    $payload = serializeItem($count->items->first(), false);

    expect($payload)->not->toHaveKey('expected_quantity')
        ->and($payload)->not->toHaveKey('variance')
        ->and($payload['counted_quantity'])->toBe(58);
});

test('a closed count sends both, because the numbers can no longer bias anyone', function () {
    $count = makeCount('completed');
    $payload = serializeItem($count->items->first(), true);

    expect($payload['expected_quantity'])->toBe(60)
        ->and($payload['variance'])->toBe(-2);
});

test('an item asked for on its own works out its own parent status', function () {
    // No flag set: the resource has to fall back to the parent rather than defaulting
    // to visible, or a standalone item request would leak what the count withholds.
    $open = makeCount('in_progress');
    expect(serializeItem($open->items->first()))->not->toHaveKey('expected_quantity');

    $closed = makeCount('completed');
    expect(serializeItem($closed->items->first()))->toHaveKey('expected_quantity');
});

test('revealing the expected quantities on an open count is written to the audit trail', function () {
    $count = makeCount('in_progress');

    $count->logAuditEvent(
        Fleetbase\Pallet\Models\AuditEventType::CYCLE_COUNT,
        'Expected Quantities Revealed',
        'expected_revealed',
        'line will not reconcile',
        ['count_number' => $count->count_number]
    );

    $audit = Audit::where('auditable_uuid', $count->uuid)->where('type', 'expected_revealed')->first();

    expect($audit)->not->toBeNull()
        ->and($audit->action)->toBe('Expected Quantities Revealed')
        ->and($audit->reason)->toBe('line will not reconcile');
});
