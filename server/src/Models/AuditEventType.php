<?php

namespace Fleetbase\Pallet\Models;

/**
 * AuditEventType — Constants for WMS operational audit event categories.
 *
 * These constants define the machine-readable `event_type` values used in the
 * pallet_audits table. They are used for filtering, grouping, and displaying
 * audit trail entries in the frontend.
 *
 * Usage:
 *   AuditEventType::STOCK_ADJUSTMENT  // 'stock_adjustment'
 *   AuditEventType::CYCLE_COUNT       // 'cycle_count'
 *   AuditEventType::PO_RECEIVED       // 'po_received'
 *   AuditEventType::SO_FULFILLED      // 'so_fulfilled'
 *   AuditEventType::STOCK_TRANSFER    // 'stock_transfer'
 *   AuditEventType::INVENTORY_CREATED // 'inventory_created'
 *   AuditEventType::INVENTORY_UPDATED // 'inventory_updated'
 *   AuditEventType::BATCH_CREATED     // 'batch_created'
 *   AuditEventType::BATCH_EXPIRED     // 'batch_expired'
 *   AuditEventType::MANUAL            // 'manual'
 */
class AuditEventType
{
    /**
     * A stock quantity was manually adjusted (positive or negative).
     * Typically triggered by StockAdjustment creation.
     */
    const STOCK_ADJUSTMENT = 'stock_adjustment';

    /**
     * A cycle count was completed and approved.
     * Triggered by CycleCount::approve().
     */
    const CYCLE_COUNT = 'cycle_count';

    /**
     * A purchase order was received into inventory.
     * Triggered by PurchaseOrderController::receive().
     */
    const PO_RECEIVED = 'po_received';

    /**
     * A sales order was fulfilled and dispatched.
     * Triggered by SalesOrderController::fulfill().
     */
    const SO_FULFILLED = 'so_fulfilled';

    /**
     * A stock transfer between warehouse locations was completed.
     * Triggered by StockTransfer completion.
     */
    const STOCK_TRANSFER = 'stock_transfer';

    /**
     * A new inventory record was created (initial stock intake).
     * Triggered by InventoryController::store().
     */
    const INVENTORY_CREATED = 'inventory_created';

    /**
     * An inventory record was updated (e.g. expiry date, location change).
     * Triggered by InventoryController::update().
     */
    const INVENTORY_UPDATED = 'inventory_updated';

    /**
     * A new batch was created and linked to inventory.
     * Triggered by BatchController::store().
     */
    const BATCH_CREATED = 'batch_created';

    /**
     * A batch was marked as expired.
     * Triggered by Batch expiry detection.
     */
    const BATCH_EXPIRED = 'batch_expired';

    /**
     * A manually created audit note with no specific system trigger.
     */
    const MANUAL = 'manual';

    /**
     * Returns all defined event type constants as an associative array.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'stock_adjustment'  => self::STOCK_ADJUSTMENT,
            'cycle_count'       => self::CYCLE_COUNT,
            'po_received'       => self::PO_RECEIVED,
            'so_fulfilled'      => self::SO_FULFILLED,
            'stock_transfer'    => self::STOCK_TRANSFER,
            'inventory_created' => self::INVENTORY_CREATED,
            'inventory_updated' => self::INVENTORY_UPDATED,
            'batch_created'     => self::BATCH_CREATED,
            'batch_expired'     => self::BATCH_EXPIRED,
            'manual'            => self::MANUAL,
        ];
    }

    /**
     * Returns all event type values as a flat array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_values(self::all());
    }
}
