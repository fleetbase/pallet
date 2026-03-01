import Component from '@glimmer/component';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { task } from 'ember-concurrency-decorators';

/**
 * FulfillSalesOrderFormPanel
 *
 * A slide-over panel that allows warehouse staff to process the fulfillment of
 * a Sales Order. Each line item shows the ordered quantity, already-fulfilled
 * quantity, and an input for the quantity being fulfilled now.
 * Supports full and partial fulfillments. On save, calls POST /sales-orders/:id/fulfill.
 * Uses FEFO (First Expired, First Out) inventory selection on the backend by default.
 */
export default class FulfillSalesOrderFormPanelComponent extends Component {
    /**
     * @service notifications
     */
    @service notifications;

    /**
     * @service fetch
     */
    @service fetch;

    /**
     * @service store
     */
    @service store;

    /**
     * Per-item fulfillment data keyed by item UUID.
     * Each entry: { quantity_fulfilled, notes }
     *
     * @type {Object}
     * @tracked
     */
    @tracked fulfillmentData = {};

    /**
     * Whether the fulfill operation is in progress.
     *
     * @type {Boolean}
     * @tracked
     */
    @tracked isFulfilling = false;

    constructor() {
        super(...arguments);
        this._initFulfillmentData();
    }

    /**
     * Returns the sales order from either the direct arg or the context panel `context` arg.
     *
     * @type {Object}
     */
    get salesOrder() {
        return this.args.salesOrder || this.args.context;
    }

    /**
     * Initialise the fulfillmentData map with one entry per pending/partial item.
     */
    _initFulfillmentData() {
        const salesOrder = this.salesOrder;
        if (!salesOrder || !salesOrder.items) {
            return;
        }

        const data = {};
        salesOrder.items.forEach((item) => {
            if (['pending', 'partial'].includes(item.status)) {
                data[item.id] = {
                    uuid: item.id,
                    quantity_fulfilled: item.outstanding_quantity || 0,
                    notes: '',
                };
            }
        });
        this.fulfillmentData = data;
    }

    /**
     * Returns the items that are eligible for fulfillment (pending or partial).
     *
     * @type {Array}
     */
    get fulfillableItems() {
        const salesOrder = this.salesOrder;
        if (!salesOrder || !salesOrder.items) {
            return [];
        }
        return salesOrder.items.filter((item) => ['pending', 'partial'].includes(item.status));
    }

    /**
     * Returns true if there are no fulfillable items.
     *
     * @type {Boolean}
     */
    get hasNoFulfillableItems() {
        return this.fulfillableItems.length === 0;
    }

    /**
     * Returns the fulfillment entry for a given item.
     *
     * @param {Object} item
     * @returns {Object}
     */
    getFulfillmentEntry(item) {
        return this.fulfillmentData[item.id] || {};
    }

    /**
     * Updates a field in the fulfillment entry for a given item.
     *
     * @action
     * @param {Object} item
     * @param {String} field
     * @param {*} value
     */
    @action updateFulfillmentField(item, field, value) {
        const current = this.fulfillmentData[item.id] || {};
        this.fulfillmentData = {
            ...this.fulfillmentData,
            [item.id]: { ...current, [field]: value },
        };
    }

    /**
     * Submits the fulfillment to the API.
     *
     * @task
     */
    @task *fulfillOrder() {
        const salesOrder = this.salesOrder;
        const items = Object.values(this.fulfillmentData).filter((entry) => entry.quantity_fulfilled > 0);

        if (items.length === 0) {
            this.notifications.warning('Please enter at least one quantity to fulfill.');
            return;
        }

        this.isFulfilling = true;

        try {
            const response = yield this.fetch.post(`sales-orders/${salesOrder.public_id}/fulfill`, { items });

            // Reload the SO record from the store to reflect updated status and items
            yield salesOrder.reload();

            this.notifications.success(`Sales Order ${salesOrder.public_id} fulfilled successfully.`);

            if (typeof this.args.onFulfilled === 'function') {
                this.args.onFulfilled(response);
            }

            if (typeof this.args.onPressCancel === 'function') {
                this.args.onPressCancel();
            }
        } catch (error) {
            const payload = error?.payload;
            if (payload?.insufficient_stock) {
                // Show a detailed insufficient stock error
                const lines = payload.insufficient_stock.map((s) => `Product ${s.product_uuid}: requested ${s.requested}, available ${s.available}`);
                this.notifications.serverError({ payload: { errors: [payload.error, ...lines] } });
            } else {
                const message = payload?.error || error?.message || 'Failed to fulfill sales order.';
                this.notifications.serverError({ payload: { errors: [message] } });
            }
        } finally {
            this.isFulfilling = false;
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
