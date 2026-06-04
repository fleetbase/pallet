import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class PurchaseOrderPanelItemsComponent extends Component {
    @service store;
    @service notifications;
    @service fetch;

    @tracked isAddingItem = false;
    @tracked editingItem = null;
    @tracked newItem = {};

    /**
     * Returns the items for this purchase order.
     *
     * @type {Array}
     */
    get items() {
        return this.args.purchaseOrder?.items ?? [];
    }

    /**
     * The order is read-only once it has been received or cancelled.
     *
     * @type {Boolean}
     */
    get isReadOnly() {
        const status = this.args.purchaseOrder?.status;
        return status === 'received' || status === 'cancelled';
    }

    /**
     * Show the new-item input row.
     */
    @action startAddingItem() {
        this.newItem = { quantity: 1, unit_price: 0 };
        this.isAddingItem = true;
    }

    /**
     * Hide the new-item input row and reset the draft.
     */
    @action cancelAddingItem() {
        this.isAddingItem = false;
        this.newItem = {};
    }

    /**
     * Persist the new line item to the API and push it into the PO's items array.
     */
    @action async addItem() {
        const purchaseOrder = this.args.purchaseOrder;
        if (!purchaseOrder) return;

        try {
            const item = await this.fetch.post(
                `purchase-orders/${purchaseOrder.id}/items`,
                {
                    purchase_order_item: {
                        product_uuid: this.newItem.product_uuid ?? this.newItem.product?.id,
                        variant_uuid: this.newItem.variant_uuid ?? this.newItem.variant?.id,
                        sku: this.newItem.sku,
                        quantity: this.newItem.quantity,
                        unit_price: this.newItem.unit_price,
                        notes: this.newItem.notes,
                    },
                },
                { namespace: 'pallet/int/v1' }
            );

            // Push into the store and update the PO's items
            const record = this.store.push(this.store.normalize('purchase-order-item', item.purchase_order_item ?? item));
            purchaseOrder.items.pushObject(record);

            this.isAddingItem = false;
            this.newItem = {};
            this.notifications.success('Line item added.');
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    /**
     * Begin inline editing of an existing item.
     *
     * @param {PurchaseOrderItemModel} item
     */
    @action startEditingItem(item) {
        // Clone the item's attributes into a plain object for editing
        this.editingItem = {
            id: item.id,
            product: item.product,
            product_uuid: item.product_uuid,
            variant: item.variant,
            variant_uuid: item.variant_uuid,
            sku: item.sku,
            quantity: item.quantity,
            unit_price: item.unit_price,
            notes: item.notes,
        };
    }

    @action setNewVariant(variant) {
        this.newItem.variant = variant;
        this.newItem.variant_uuid = variant?.uuid;
        if (variant?.sku && !this.newItem.sku) {
            this.newItem.sku = variant.sku;
        }
    }

    @action setEditingVariant(variant) {
        this.editingItem.variant = variant;
        this.editingItem.variant_uuid = variant?.uuid;
        if (variant?.sku && !this.editingItem.sku) {
            this.editingItem.sku = variant.sku;
        }
    }

    /**
     * Cancel inline editing without saving.
     */
    @action cancelEditing() {
        this.editingItem = null;
    }

    /**
     * Persist the edited item to the API and update the store record.
     */
    @action async saveEditingItem() {
        const purchaseOrder = this.args.purchaseOrder;
        if (!this.editingItem || !purchaseOrder) return;

        try {
            await this.fetch.put(
                `purchase-orders/${purchaseOrder.id}/items/${this.editingItem.id}`,
                {
                    purchase_order_item: {
                        product_uuid: this.editingItem.product_uuid ?? this.editingItem.product?.id,
                        variant_uuid: this.editingItem.variant_uuid ?? this.editingItem.variant?.id,
                        sku: this.editingItem.sku,
                        quantity: this.editingItem.quantity,
                        unit_price: this.editingItem.unit_price,
                        notes: this.editingItem.notes,
                    },
                },
                { namespace: 'pallet/int/v1' }
            );

            // Reload the item from the store to reflect server-calculated fields
            const storeItem = this.store.peekRecord('purchase-order-item', this.editingItem.id);
            if (storeItem) {
                storeItem.reload();
            }

            this.editingItem = null;
            this.notifications.success('Line item updated.');
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    /**
     * Delete a line item from the PO.
     *
     * @param {PurchaseOrderItemModel} item
     */
    @action async removeItem(item) {
        const purchaseOrder = this.args.purchaseOrder;
        if (!purchaseOrder) return;

        try {
            await this.fetch.delete(`purchase-orders/${purchaseOrder.id}/items/${item.id}`, {}, { namespace: 'pallet/int/v1' });
            purchaseOrder.items.removeObject(item);
            this.notifications.success('Line item removed.');
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
