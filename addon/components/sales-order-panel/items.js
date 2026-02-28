import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class SalesOrderPanelItemsComponent extends Component {
    @service store;
    @service notifications;
    @service fetch;

    @tracked isAddingItem = false;
    @tracked editingItem = null;
    @tracked newItem = {};

    /**
     * Returns the items for this sales order.
     *
     * @type {Array}
     */
    get items() {
        return this.args.salesOrder?.items ?? [];
    }

    /**
     * The order is read-only once it has been fulfilled or cancelled.
     *
     * @type {Boolean}
     */
    get isReadOnly() {
        const status = this.args.salesOrder?.status;
        return status === 'fulfilled' || status === 'cancelled';
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
     * Persist the new line item to the API and push it into the SO's items array.
     */
    @action async addItem() {
        const salesOrder = this.args.salesOrder;
        if (!salesOrder) return;

        try {
            const item = await this.fetch.post(`sales-orders/${salesOrder.id}/items`, {
                sales_order_item: {
                    product_uuid: this.newItem.product_uuid ?? this.newItem.product?.id,
                    sku: this.newItem.sku,
                    quantity: this.newItem.quantity,
                    unit_price: this.newItem.unit_price,
                    notes: this.newItem.notes,
                },
            });

            const record = this.store.push(this.store.normalize('sales-order-item', item.sales_order_item ?? item));
            salesOrder.items.pushObject(record);

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
     * @param {SalesOrderItemModel} item
     */
    @action startEditingItem(item) {
        this.editingItem = {
            id: item.id,
            product: item.product,
            product_uuid: item.product_uuid,
            sku: item.sku,
            quantity: item.quantity,
            unit_price: item.unit_price,
            notes: item.notes,
        };
    }

    /**
     * Cancel inline editing without saving.
     */
    @action cancelEditing() {
        this.editingItem = null;
    }

    /**
     * Persist the edited item to the API and reload the store record.
     */
    @action async saveEditingItem() {
        const salesOrder = this.args.salesOrder;
        if (!this.editingItem || !salesOrder) return;

        try {
            await this.fetch.put(`sales-orders/${salesOrder.id}/items/${this.editingItem.id}`, {
                sales_order_item: {
                    product_uuid: this.editingItem.product_uuid ?? this.editingItem.product?.id,
                    sku: this.editingItem.sku,
                    quantity: this.editingItem.quantity,
                    unit_price: this.editingItem.unit_price,
                    notes: this.editingItem.notes,
                },
            });

            const storeItem = this.store.peekRecord('sales-order-item', this.editingItem.id);
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
     * Delete a line item from the SO.
     *
     * @param {SalesOrderItemModel} item
     */
    @action async removeItem(item) {
        const salesOrder = this.args.salesOrder;
        if (!salesOrder) return;

        try {
            await this.fetch.delete(`sales-orders/${salesOrder.id}/items/${item.id}`);
            salesOrder.items.removeObject(item);
            this.notifications.success('Line item removed.');
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
