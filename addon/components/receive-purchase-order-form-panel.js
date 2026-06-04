import Component from '@glimmer/component';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { task } from 'ember-concurrency-decorators';

/**
 * ReceivePurchaseOrderFormPanel
 *
 * A slide-over panel that allows warehouse staff to process the receipt of
 * goods against a Purchase Order. Each line item shows the ordered quantity,
 * already-received quantity, and an input for the quantity being received now.
 * Supports full and partial receipts. On save, calls POST /purchase-orders/:id/receive.
 */
export default class ReceivePurchaseOrderFormPanelComponent extends Component {
    /**
     * @service notifications
     */
    @service notifications;

    /**
     * @service modalsManager
     */
    @service modalsManager;

    /**
     * @service fetch
     */
    @service fetch;

    /**
     * @service store
     */
    @service store;

    /**
     * The per-item receipt data keyed by item UUID.
     * Each entry: { quantity_received, lot_number, serial_number, expiry_date, notes }
     *
     * @type {Object}
     * @tracked
     */
    @tracked receiptData = {};

    constructor() {
        super(...arguments);
        this._initReceiptData();
    }

    /**
     * Returns the purchase order from either the direct arg or the context panel `context` arg.
     *
     * @type {Object}
     */
    get purchaseOrder() {
        return this.args.purchaseOrder || this.args.context;
    }

    /**
     * Initialise the receiptData map with one entry per pending/partial item.
     */
    _initReceiptData() {
        const purchaseOrder = this.purchaseOrder;
        if (!purchaseOrder || !purchaseOrder.items) {
            return;
        }

        const data = {};
        purchaseOrder.items.forEach((item) => {
            if (['pending', 'partial'].includes(item.status)) {
                data[item.id] = {
                    uuid: item.id,
                    quantity_received: item.outstanding_quantity || 0,
                    lot_number: item.lot_number || '',
                    serial_number: item.serial_number || '',
                    expiry_date: '',
                    notes: '',
                };
            }
        });
        this.receiptData = data;
    }

    /**
     * Returns the items that are eligible for receipt (pending or partial).
     *
     * @type {Array}
     */
    get receivableItems() {
        const purchaseOrder = this.purchaseOrder;
        if (!purchaseOrder || !purchaseOrder.items) {
            return [];
        }
        return purchaseOrder.items.filter((item) => ['pending', 'partial'].includes(item.status));
    }

    /**
     * Returns true if there are no receivable items.
     *
     * @type {Boolean}
     */
    get hasNoReceivableItems() {
        return this.receivableItems.length === 0;
    }

    /**
     * Returns the receipt entry for a given item.
     *
     * @param {Object} item
     * @returns {Object}
     */
    getReceiptEntry(item) {
        return this.receiptData[item.id] || {};
    }

    /**
     * Updates a field in the receipt entry for a given item.
     *
     * @action
     * @param {Object} item
     * @param {String} field
     * @param {*} value
     */
    @action updateReceiptField(item, field, value) {
        const current = this.receiptData[item.id] || {};
        this.receiptData = {
            ...this.receiptData,
            [item.id]: { ...current, [field]: value },
        };
    }

    /**
     * Submits the receipt to the API.
     *
     * @task
     */
    @task *receiveOrder() {
        const purchaseOrder = this.purchaseOrder;
        const items = Object.values(this.receiptData).filter((entry) => entry.quantity_received > 0);

        if (items.length === 0) {
            this.notifications.warning('Please enter at least one quantity to receive.');
            return;
        }

        try {
            const response = yield this.fetch.post(`purchase-orders/${purchaseOrder.public_id}/receive`, { items }, { namespace: 'pallet/int/v1' });

            // Reload the PO record from the store to reflect updated status and items
            yield purchaseOrder.reload();

            this.notifications.success(`Purchase Order ${purchaseOrder.public_id} received successfully.`);

            if (typeof this.args.onReceived === 'function') {
                this.args.onReceived(response);
            }

            if (typeof this.args.onPressCancel === 'function') {
                this.args.onPressCancel();
            }
        } catch (error) {
            const message = error?.payload?.error || error?.message || 'Failed to receive purchase order.';
            this.notifications.serverError({ payload: { errors: [message] } });
        }
    }

    /**
     * Handles the cancel/close action.
     *
     * @action
     */
    @action onPressCancel() {
        if (typeof this.args.onPressCancel === 'function') {
            this.args.onPressCancel();
        }
    }
}
