import Component from '@glimmer/component';
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
 */
export default class InventoryLedgerComponent extends Component {
    @service store;
    @service notifications;

    get inventoryUuid() {
        return this.args.resource?.uuid ?? this.args.resource?.id;
    }

    /**
     * `balance_after` only exists for movements recorded after the column was added.
     * Historical rows carry null, which the template renders as an em dash rather than
     * a zero — an unknown balance and a balance of zero are different facts.
     */
    load = task({ drop: true }, async () => {
        const inventory = this.inventoryUuid;

        if (!inventory) {
            return [];
        }

        try {
            return await this.store.query('stock-transaction', {
                inventory,
                sort: '-transaction_date_at',
                limit: 50,
            });
        } catch (error) {
            this.notifications.serverError(error);
            return [];
        }
    });
}
