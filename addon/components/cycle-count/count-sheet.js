import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';
import { scheduleOnce } from '@ember/runloop';

/**
 * The count sheet — SCREENS.md §F's `.count` screen.
 *
 * Blind by design. Expected quantity is withheld, per §F and Ron's accepted decision 3,
 * because seeing it biases whoever is counting.
 *
 * **Variance is withheld for the same reason, which the spec's row list does not spell
 * out.** Variance is counted minus expected, so a visible variance beside a visible
 * counted quantity gives expected away by subtraction. Hiding one and showing the other
 * would have been blind counting in name only. Both appear on the document once the
 * count is completed.
 *
 * There is no supervisor reveal here yet. The decision requires the reveal to be written
 * to the audit trail, and no endpoint records it; an unaudited toggle is the thing the
 * decision guards against, so the sheet stays blind until that endpoint exists.
 *
 * Generator tasks rather than `task(async () => {})` — the async-arrow form is not
 * compiled by Babel in this engine and throws on construction.
 */
export default class CycleCountSheetComponent extends Component {
    @service fetch;
    @service notifications;
    @service intl;
    @service hostRouter;

    /** Counted values keyed by item uuid, so typing never mutates the records. */
    @tracked entries = {};
    @tracked scan = '';
    @tracked focusedUuid = null;
    @tracked lastError = null;

    /**
     * Rows in a fixed order, captured once.
     *
     * §11.4's must-never is re-sorting while the counter is typing. Sorting by anything
     * that changes as counts are entered — status, variance, whether a line is done —
     * would move the row out from under the cursor, so the order is the bin walk and
     * nothing else re-orders it.
     */
    get rows() {
        const items = [...(this.args.resource?.items ?? [])];

        items.sort((a, b) => {
            const left = a.binLocation?.bin_number ?? '';
            const right = b.binLocation?.bin_number ?? '';

            return left.localeCompare(right) || (a.public_id ?? '').localeCompare(b.public_id ?? '');
        });

        return items.map((item) => ({
            item,
            uuid: item.uuid ?? item.id,
            entered: this.entries[item.uuid ?? item.id],
            isCounted: item.status === 'counted',
            isFocused: (item.uuid ?? item.id) === this.focusedUuid,
        }));
    }

    get summary() {
        return this.rows.reduce(
            (totals, row) => {
                totals.lines += 1;

                if (row.isCounted) {
                    totals.counted += 1;
                }

                return totals;
            },
            { lines: 0, counted: 0 }
        );
    }

    get isOpen() {
        return this.args.resource?.status === 'in_progress';
    }

    get canSubmit() {
        return this.isOpen && this.summary.counted > 0;
    }

    /**
     * The scan field owns the cursor from the moment the sheet opens — a counter with a
     * scanner should never have to click before their first scan registers.
     */
    @action registerScanField(element) {
        this.scanField = element;
        element.focus();
    }

    /** Hands the cursor back to the scan field after a line is recorded. */
    @action returnFocusToScan() {
        if (this.scanField) {
            this.scanField.focus();
        }
    }

    @action setScan(event) {
        this.scan = event.target.value;
    }

    @action setEntry(uuid, event) {
        this.entries = { ...this.entries, [uuid]: event.target.value };
    }

    @action focusRow(uuid) {
        this.focusedUuid = uuid;
    }

    /**
     * A scan matches a bin number or a product SKU and moves the cursor to that row.
     * §9's must-never is losing scan focus, so the field keeps it and the match only
     * highlights — the counter's hands never leave the scanner.
     */
    @action onScan(event) {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        const term = (this.scan ?? '').trim().toLowerCase();

        if (!term) {
            return;
        }

        const match = this.rows.find((row) => {
            const bin = (row.item.binLocation?.bin_number ?? '').toLowerCase();
            const sku = (row.item.product?.sku ?? '').toLowerCase();
            const name = (row.item.product?.name ?? '').toLowerCase();

            return bin === term || sku === term || name.includes(term);
        });

        if (!match) {
            this.lastError = this.intl.t('operations.cycle-counts.count.no-match', { term: this.scan });
            return;
        }

        this.lastError = null;
        this.focusedUuid = match.uuid;
        this.scan = '';

        // After render, not now: clearing the scan field and highlighting the row both
        // trigger a re-render, and focus set synchronously here is lost when that
        // render replaces the element. This is why the first scan appeared to work —
        // the row highlighted — while the keystrokes that followed went nowhere.
        scheduleOnce('afterRender', this, this.focusCountInput, match.uuid);
    }

    focusCountInput(uuid) {
        const input = document.querySelector(`[data-count-input="${uuid}"]`);

        if (input) {
            input.focus();
            input.select();
        }
    }

    /**
     * Enter in a count field records that line and hands the cursor back to the scanner.
     * §F's must-never for this screen is requiring a mouse to complete a line.
     */
    @action onCountKey(row, event) {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        this.recordLine.perform(row);
    }

    @task({ drop: true })
    *recordLine(row) {
        const value = this.entries[row.uuid];

        if (value === undefined || value === null || `${value}`.trim() === '') {
            return;
        }

        const quantity = Number(value);

        if (isNaN(quantity) || quantity < 0) {
            this.notifications.warning(this.intl.t('operations.cycle-counts.count.invalid-quantity'));
            return;
        }

        try {
            yield this.fetch.post(`cycle-count-items/${row.item.public_id ?? row.uuid}/record-count`, { counted_quantity: quantity }, { namespace: 'pallet/int/v1' });

            // Reload the parent so the row's own status and the header counts move
            // together; patching the record locally would leave the two disagreeing if
            // the server clamped or rejected anything.
            yield this.args.resource.reload();
            this.entries = { ...this.entries, [row.uuid]: '' };
            this.returnFocusToScan();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @task({ drop: true })
    *submitCount() {
        try {
            yield this.fetch.post(`cycle-counts/${this.args.resource.public_id}/complete`, {}, { namespace: 'pallet/int/v1' });
            this.notifications.success(this.intl.t('operations.cycle-counts.count.submitted'));
            yield this.hostRouter.transitionTo('console.pallet.operations.cycle-counts.details', this.args.resource.public_id);
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
