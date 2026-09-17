import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';
import { scheduleOnce } from '@ember/runloop';

/**
 * The pick screen — SCREENS.md §F's `.pick`.
 *
 * §F's must-never for this resource is presenting the whole route as an editable table
 * with no current-line focus. A picker is walking, holding a scanner, looking at one
 * bin: the screen shows one line large, and the rest of the route below it as context
 * they are walking towards, not as a grid to edit.
 *
 * The second must-never is letting a short pick block the remaining lines. Short is an
 * ordinary outcome here — the stock was not there — so it records what was found with a
 * reason and advances, exactly like a full pick.
 *
 * Skip is client-side only and deliberately so: skipping is "not now", not an outcome.
 * Nothing is written, the line stays open, and it comes back at the end of the route.
 */
export default class PickListPickScreenComponent extends Component {
    @service fetch;
    @service notifications;
    @service intl;
    @service hostRouter;

    @tracked scan = '';
    @tracked shortQuantity = '';
    @tracked shortReason = '';
    @tracked isShorting = false;
    @tracked skipped = [];
    @tracked lastError = null;

    scanField = null;

    /**
     * The route in walk order. Ordered once by sequence and never re-ordered as lines
     * are picked — §11.4, and the same reason the count sheet fixes its order: the row
     * the picker is looking at has to stay where it is.
     */
    get route() {
        const items = [...(this.args.resource?.items ?? [])];

        items.sort((a, b) => (Number(a.sequence_number) || 0) - (Number(b.sequence_number) || 0));

        return items.map((item) => {
            const uuid = item.uuid ?? item.id;
            const requested = Number(item.quantity_requested) || 0;
            const picked = Number(item.quantity_picked) || 0;

            return {
                item,
                uuid,
                requested,
                picked,
                isDone: item.status === 'picked',
                isShort: item.status === 'picked' && picked < requested,
                isSkipped: this.skipped.includes(uuid),
            };
        });
    }

    /**
     * The line the picker is at: the first that is neither picked nor skipped. Skipped
     * lines fall to the back rather than out — once nothing else is left, they are what
     * remains.
     */
    get currentLine() {
        const open = this.route.filter((line) => !line.isDone);

        return open.find((line) => !line.isSkipped) ?? open[0] ?? null;
    }

    get remaining() {
        const current = this.currentLine;

        // By uuid, not identity: `route` rebuilds its line objects on every access, so
        // the object `currentLine` returned is never the same one this filter sees, and
        // an identity check left the current line showing in Up next as well.
        return this.route.filter((line) => !line.isDone && line.uuid !== current?.uuid);
    }

    get progress() {
        return this.route.reduce(
            (totals, line) => {
                totals.lines += 1;

                if (line.isDone) {
                    totals.picked += 1;
                }

                if (line.isShort) {
                    totals.short += 1;
                }

                return totals;
            },
            { lines: 0, picked: 0, short: 0 }
        );
    }

    get isOpen() {
        return this.args.resource?.status === 'in_progress';
    }

    get isComplete() {
        return this.progress.lines > 0 && this.progress.picked === this.progress.lines;
    }

    @action registerScanField(element) {
        this.scanField = element;
        element.focus();
    }

    @action setScan(event) {
        this.scan = event.target.value;
    }

    @action setShortQuantity(event) {
        this.shortQuantity = event.target.value;
    }

    @action setShortReason(event) {
        this.shortReason = event.target.value;
    }

    focusScan() {
        if (this.scanField) {
            this.scanField.focus();
        }
    }

    /**
     * A scan confirms the current line when it matches, which is the whole interaction:
     * walk to the bin, scan it, move on. §F's FEFO rule lives here too — a lot-tracked
     * line names its lot, and scanning a different one is refused rather than silently
     * accepted.
     */
    @action onScan(event) {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        const term = (this.scan ?? '').trim().toLowerCase();
        const line = this.currentLine;

        if (!term || !line) {
            return;
        }

        const bin = (line.item.binLocation?.bin_number ?? '').toLowerCase();
        const sku = (line.item.product?.sku ?? '').toLowerCase();
        const lot = (line.item.lot_number ?? '').toLowerCase();

        if (lot && term === lot) {
            this.scan = '';
            this.lastError = null;
            this.confirmPick.perform(line);
            return;
        }

        if (term === bin || term === sku) {
            // A named lot must be scanned to be picked; a bin or SKU scan is not proof
            // the right lot came off the shelf.
            if (lot) {
                this.lastError = this.intl.t('operations.pick-lists.pick.lot-required', { lot: line.item.lot_number });
                this.scan = '';
                return;
            }

            this.scan = '';
            this.lastError = null;
            this.confirmPick.perform(line);
            return;
        }

        this.lastError = this.intl.t('operations.pick-lists.pick.no-match', { term: this.scan });
        this.scan = '';
    }

    @action startShort() {
        const line = this.currentLine;

        this.isShorting = true;
        this.shortQuantity = line ? String(Math.max(line.requested - 1, 0)) : '';
        this.shortReason = '';
    }

    @action cancelShort() {
        this.isShorting = false;
        this.shortQuantity = '';
        this.shortReason = '';
        scheduleOnce('afterRender', this, this.focusScan);
    }

    /**
     * Skipping writes nothing. The line is still open and still owed; it simply is not
     * the one being walked to next.
     */
    @action skipLine() {
        const line = this.currentLine;

        if (!line) {
            return;
        }

        this.skipped = [...this.skipped, line.uuid];
        this.lastError = null;
        scheduleOnce('afterRender', this, this.focusScan);
    }

    @task({ drop: true })
    *confirmPick(line, quantity = null, notes = null) {
        const picked = quantity === null ? line.requested : Number(quantity);

        if (isNaN(picked) || picked <= 0 || picked > line.requested) {
            this.notifications.warning(this.intl.t('operations.pick-lists.pick.invalid-quantity', { requested: line.requested }));
            return;
        }

        try {
            yield this.fetch.post(`pick-list-items/${line.item.public_id ?? line.uuid}/picked`, { quantity_picked: picked, notes }, { namespace: 'pallet/int/v1' });
            yield this.args.resource.reload();

            this.isShorting = false;
            this.shortQuantity = '';
            this.shortReason = '';
            // Skipped lines are cleared as the route shortens, so a skipped line does
            // come back rather than being lost.
            this.skipped = this.skipped.filter((uuid) => uuid !== line.uuid);
            scheduleOnce('afterRender', this, this.focusScan);
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action submitShort() {
        const line = this.currentLine;

        if (!line) {
            return;
        }

        if (!this.shortReason.trim()) {
            this.notifications.warning(this.intl.t('operations.pick-lists.pick.reason-required'));
            return;
        }

        this.confirmPick.perform(line, this.shortQuantity, this.shortReason);
    }

    @task({ drop: true })
    *completePickList() {
        try {
            yield this.fetch.post(`pick-lists/${this.args.resource.public_id}/complete`, {}, { namespace: 'pallet/int/v1' });
            this.notifications.success(this.intl.t('operations.pick-lists.pick.completed'));
            yield this.hostRouter.transitionTo('console.pallet.operations.pick-lists.details', this.args.resource.public_id);
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
