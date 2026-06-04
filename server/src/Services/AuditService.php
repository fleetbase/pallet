<?php

namespace Fleetbase\Pallet\Services;

use Fleetbase\Pallet\Models\Audit;
use Fleetbase\Pallet\Models\AuditEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * AuditService.
 *
 * Centralised service for writing entries to the pallet_audits operational
 * audit trail. This service is the single point of entry for all programmatic
 * audit logging within the Pallet WMS module.
 *
 * It is used by controllers, model observers, and model boot hooks to record
 * significant warehouse operational events. It is NOT used for low-level model
 * attribute change logging — that is handled automatically by the Spatie
 * laravel-activitylog package via the LogsActivity trait.
 *
 * Usage:
 *   app(AuditService::class)->log(
 *       $stockAdjustment,
 *       AuditEventType::STOCK_ADJUSTMENT,
 *       'Stock Adjusted',
 *       'damage',
 *       $stockAdjustment->reason,
 *       ['before_quantity' => 10, 'after_quantity' => 8]
 *   );
 */
class AuditService
{
    /**
     * Log a general operational audit event.
     *
     * @param Model       $subject   The primary model this event relates to
     * @param string      $eventType Machine-readable event category (AuditEventType constant)
     * @param string      $action    Human-readable label for the event
     * @param string|null $type      Secondary classification within the event type
     * @param string|null $reason    Reason code or user-supplied explanation
     * @param array       $meta      Arbitrary structured context data
     * @param array       $oldValues State snapshot before the event
     * @param array       $newValues State snapshot after the event
     * @param string|null $comments  Free-form notes
     */
    public function log(
        Model $subject,
        string $eventType,
        string $action,
        ?string $type = null,
        ?string $reason = null,
        array $meta = [],
        array $oldValues = [],
        array $newValues = [],
        ?string $comments = null,
    ): Audit {
        $performedByUuid = Auth::id() ?? null;
        $companyUuid     = $subject->company_uuid ?? null;

        return Audit::create([
            'company_uuid'      => $companyUuid,
            'created_by_uuid'   => $performedByUuid,
            'performed_by_uuid' => $performedByUuid,
            'auditable_uuid'    => $subject->uuid,
            'auditable_type'    => get_class($subject),
            'event_type'        => $eventType,
            'action'            => $action,
            'type'              => $type,
            'reason'            => $reason,
            'comments'          => $comments,
            'old_values'        => $oldValues ?: null,
            'new_values'        => $newValues ?: null,
            'meta'              => $meta ?: null,
            'completed_at'      => now(),
        ]);
    }

    /**
     * Log a stock adjustment event.
     */
    public function logStockAdjustment(\Fleetbase\Pallet\Models\StockAdjustment $adjustment): Audit
    {
        return $this->log(
            $adjustment,
            AuditEventType::STOCK_ADJUSTMENT,
            'Stock Adjusted',
            $adjustment->type,
            $adjustment->reason,
            [
                'product_uuid'    => $adjustment->product_uuid,
                'before_quantity' => $adjustment->before_quantity,
                'after_quantity'  => $adjustment->after_quantity,
                'quantity_delta'  => $adjustment->quantity,
            ]
        );
    }

    /**
     * Log a cycle count completion event.
     */
    public function logCycleCountCompleted(\Fleetbase\Pallet\Models\CycleCount $cycleCount): Audit
    {
        return $this->log(
            $cycleCount,
            AuditEventType::CYCLE_COUNT,
            'Cycle Count Completed',
            'completed',
            null,
            [
                'count_number'        => $cycleCount->count_number,
                'warehouse_uuid'      => $cycleCount->warehouse_uuid,
                'total_items'         => $cycleCount->total_items,
                'discrepancies_count' => $cycleCount->discrepancies_count,
                'accuracy_percentage' => $cycleCount->accuracy_percentage,
            ]
        );
    }

    /**
     * Log a cycle count approval event (discrepancies applied to inventory).
     */
    public function logCycleCountApproved(\Fleetbase\Pallet\Models\CycleCount $cycleCount): Audit
    {
        return $this->log(
            $cycleCount,
            AuditEventType::CYCLE_COUNT,
            'Cycle Count Approved',
            'approved',
            null,
            [
                'count_number'        => $cycleCount->count_number,
                'warehouse_uuid'      => $cycleCount->warehouse_uuid,
                'discrepancies_count' => $cycleCount->discrepancies_count,
                'accuracy_percentage' => $cycleCount->accuracy_percentage,
            ]
        );
    }

    /**
     * Log a purchase order received event.
     *
     * @param array $receivedItems Array of received line items with quantities
     */
    public function logPurchaseOrderReceived(\Fleetbase\Pallet\Models\PurchaseOrder $purchaseOrder, array $receivedItems = []): Audit
    {
        return $this->log(
            $purchaseOrder,
            AuditEventType::PO_RECEIVED,
            'Purchase Order Received',
            'received',
            null,
            [
                'order_number'    => $purchaseOrder->public_id,
                'supplier_uuid'   => $purchaseOrder->supplier_uuid,
                'received_items'  => $receivedItems,
            ]
        );
    }

    /**
     * Log a sales order fulfilled event.
     *
     * @param array $fulfilledItems Array of fulfilled line items with quantities
     */
    public function logSalesOrderFulfilled(\Fleetbase\Pallet\Models\SalesOrder $salesOrder, array $fulfilledItems = []): Audit
    {
        return $this->log(
            $salesOrder,
            AuditEventType::SO_FULFILLED,
            'Sales Order Fulfilled',
            'fulfilled',
            null,
            [
                'order_number'    => $salesOrder->public_id,
                'customer_uuid'   => $salesOrder->customer_uuid,
                'fulfilled_items' => $fulfilledItems,
            ]
        );
    }

    /**
     * Log a stock transfer initiated event.
     */
    public function logStockTransferInitiated(\Fleetbase\Pallet\Models\StockTransfer $transfer): Audit
    {
        return $this->log(
            $transfer,
            AuditEventType::STOCK_TRANSFER,
            'Stock Transfer Initiated',
            'initiated',
            null,
            [
                'from_warehouse_uuid' => $transfer->from_warehouse_uuid,
                'to_warehouse_uuid'   => $transfer->to_warehouse_uuid,
                'status'              => $transfer->status,
            ]
        );
    }

    /**
     * Log a stock transfer completed event.
     */
    public function logStockTransferCompleted(\Fleetbase\Pallet\Models\StockTransfer $transfer): Audit
    {
        return $this->log(
            $transfer,
            AuditEventType::STOCK_TRANSFER,
            'Stock Transfer Completed',
            'completed',
            null,
            [
                'from_warehouse_uuid' => $transfer->from_warehouse_uuid,
                'to_warehouse_uuid'   => $transfer->to_warehouse_uuid,
            ]
        );
    }

    /**
     * Log an inventory creation event.
     */
    public function logInventoryCreated(\Fleetbase\Pallet\Models\Inventory $inventory): Audit
    {
        return $this->log(
            $inventory,
            AuditEventType::INVENTORY_CREATED,
            'Inventory Created',
            null,
            null,
            [
                'product_uuid'    => $inventory->product_uuid,
                'warehouse_uuid'  => $inventory->warehouse_uuid,
                'quantity'        => $inventory->quantity,
                'unit_price'      => $inventory->unit_price,
            ]
        );
    }

    /**
     * Log a batch creation event.
     */
    public function logBatchCreated(\Fleetbase\Pallet\Models\Batch $batch): Audit
    {
        return $this->log(
            $batch,
            AuditEventType::BATCH_CREATED,
            'Batch Created',
            null,
            null,
            [
                'batch_number'   => $batch->batch_number,
                'product_uuid'   => $batch->product_uuid,
                'quantity'       => $batch->quantity,
                'expiry_date_at' => $batch->expiry_date_at,
            ]
        );
    }
}
