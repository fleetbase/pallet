<?php

namespace Server\tests;

use Fleetbase\Pallet\Http\Resources\Internal\v1\Audit as AuditResource;
use Fleetbase\Pallet\Models\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/*
 * The audit list showed the raw auditable_uuid under a column headed "Subject ID",
 * which tells a reader nothing about which order or count a row refers to. Every
 * event this module logs already carries a readable number in its meta.
 */

function auditWithMeta(array $meta): Audit
{
    $company = (string) Str::uuid();
    session(['company' => $company]);

    return Audit::create([
        'company_uuid'   => $company,
        'auditable_uuid' => (string) Str::uuid(),
        'auditable_type' => 'Fleetbase\\Pallet\\Models\\SalesOrder',
        'event_type'     => 'sales_order',
        'action'         => 'Sales Order Fulfilled',
        'meta'           => $meta,
    ]);
}

test('an audit reports the readable number its event recorded', function () {
    $audit = auditWithMeta(['order_number' => 'sales_order_uplsnrsr3d', 'customer_uuid' => null]);

    expect($audit->subject_reference)->toBe('sales_order_uplsnrsr3d');

    $payload = (new AuditResource($audit))->toArray(Request::create('/pallet/int/v1/audits'));

    expect($payload)->toHaveKey('subject_reference')
        ->and($payload['subject_reference'])->toBe('sales_order_uplsnrsr3d');
});

test('each operational event type contributes its own number', function () {
    expect(auditWithMeta(['transfer_number' => 'TR-ABC'])->subject_reference)->toBe('TR-ABC')
        ->and(auditWithMeta(['count_number' => 'CC-ABC'])->subject_reference)->toBe('CC-ABC')
        ->and(auditWithMeta(['wave_number' => 'WAVE-ABC'])->subject_reference)->toBe('WAVE-ABC');
});

test('an audit with no readable number still identifies its subject', function () {
    $audit = auditWithMeta(['warehouse_uuid' => (string) Str::uuid()]);

    expect($audit->subject_reference)->toBe($audit->auditable_uuid, 'the uuid remains the last resort, not the default');
});
