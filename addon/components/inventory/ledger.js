import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

/**
 * The stock movement ledger for one inventory record.
 *
 * SCREENS.md §D's must-never for this panel is "require leaving the panel to see why
 * a number is what it is". A quantity on the detail screen is the sum of every
 * movement against the record, and until now the only way to see those movements was
 * to go somewhere else — so the panel showed the answer and hid the working.
 *
 * Loaded on demand rather than with the record: the panel is collapsed by default and
 * most visits never open it, so eagerly fetching the ledger would put a request on
 * every inventory view to serve the minority that want it.
 *
 * Generator syntax, not `task(async () => {})`. The async-arrow form is not compiled
 * by Babel in this engine and throws on component construction — every other task in
 * this addon uses the decorator-plus-generator form for the same reason.
 */
export default class InventoryLedgerComponent extends Component {
    @service store;
    @service notifications;

    @tracked movements = [];
    @tracked error = null;

    get inventoryUuid() {
        return this.args.resource?.uuid ?? this.args.resource?.id;
    }

    /**
     * `balance_after` only exists for movements recorded after the column was added.
     * Historical rows carry null, which the template renders as an em dash rather than
     * a zero — an unknown balance and a balance of zero are different facts.
     */
    @task({ drop: true })
    *load() {
        const inventory = this.inventoryUuid;

        if (!inventory) {
            return;
        }

        try {
            this.movements = yield this.store.query('stock-transaction', {
                inventory,
                sort: '-transaction_date_at',
                limit: 50,
            });
            this.error = null;
        } catch (error) {
            this.error = error;
            this.notifications.serverError(error);
        }
    }
}
