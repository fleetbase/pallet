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
                    inventory: item.inventory,
                    inventory_uuid: item.inventory_uuid,
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
    // Called from the template as (getFulfillmentEntry item), which passes the
    // method as a value — without @action it arrives unbound, `this` is
    // undefined, and the panel threw while rendering and vanished.
    @action
    getFulfillmentEntry(item) {
        return this.fulfillmentData[item.id] || {};
    }

    valueFromEvent(value) {
        return value?.target ? value.target.value : value;
    }

    normalizeQuantity(value, maxQuantity) {
        const numericValue = Number(this.valueFromEvent(value));
        const normalized = Number.isFinite(numericValue) ? numericValue : 0;
        return Math.max(0, Math.min(normalized, Number(maxQuantity) || 0));
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
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
        const normalizedValue = field === 'quantity_fulfilled' ? this.normalizeQuantity(value, item.outstanding_quantity) : this.valueFromEvent(value);
        this.fulfillmentData = {
            ...this.fulfillmentData,
            [item.id]: { ...current, [field]: normalizedValue },
        };
    }

    @action setFulfillmentInventory(item, inventory) {
        const current = this.fulfillmentData[item.id] || {};
        this.fulfillmentData = {
            ...this.fulfillmentData,
            [item.id]: {
                ...current,
                inventory,
                inventory_uuid: this.getRecordUuid(inventory),
            },
        };
    }

    /**
     * Submits the fulfillment to the API.
     *
     * @task
     */
    @task *fulfillOrder() {
        const salesOrder = this.salesOrder;
        const items = Object.values(this.fulfillmentData).filter((entry) => Number(entry.quantity_fulfilled) > 0);

        if (items.length === 0) {
            this.notifications.warning('Please enter at least one quantity to fulfill.');
            return;
        }

        try {
            // rawError so the whole body reaches the catch: the fetch service otherwise
            // rejects with a bare Error built from `error`, and the insufficient_stock
            // detail the endpoint sends is thrown away before anyone can show it
            const response = yield this.fetch.post(`sales-orders/${salesOrder.public_id}/fulfill`, { items }, { namespace: 'pallet/int/v1', rawError: true });

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
            // notifications.serverError reads `errors` off the object it is given.
            // These calls nested it under `payload`, so every failure here — including
            // a precise "requested 500, available 118" from the server — surfaced as
            // the generic "Oops! Something went wrong with your request."
            const shortfalls = error?.insufficient_stock ?? error?.payload?.insufficient_stock;

            if (shortfalls) {
                const lines = shortfalls.map((s) => `${this.describeShortfallItem(s)}: requested ${s.requested}, available ${s.available}`);

                return this.notifications.serverError({ errors: [error.error ?? 'Insufficient stock for one or more items.', ...lines] });
            }

            const message = error?.error || error?.payload?.error || error?.message || 'Failed to fulfill sales order.';

            this.notifications.serverError({ errors: [message] });
        }
    }

    /**
     * The endpoint identifies a shortfall by uuid, which means nothing to a reader.
     * Resolve it back to the line item's product name where we can.
     */
    describeShortfallItem(shortfall) {
        const item = (this.salesOrder?.items ?? []).find((i) => i.uuid === shortfall.item_uuid);
        const name = item?.product?.name ?? item?.name;
        const variant = item?.variant?.display_name ?? item?.variant?.name;

        if (name) {
            return variant ? `${name} (${variant})` : name;
        }

        return `Product ${shortfall.product_uuid}`;
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
