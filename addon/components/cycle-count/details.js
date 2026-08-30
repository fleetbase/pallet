import Component from '@glimmer/component';

/**
 * One cycle count, reviewed after the fact.
 *
 * The expected quantity is the sensitive field here. SCREENS.md §F and the accepted
 * design decision both say blind counting is the convention: showing the expected
 * number while a count is open biases whoever is entering it, and any reveal has to be
 * recorded in the audit trail to keep the count defensible.
 *
 * That rule is written for the `.count` sheet, but the same number on this screen would
 * bias just as effectively while the count is still open, so this document hides
 * expected and variance until counting has closed — `completed` or `approved`. After
 * that the numbers cannot influence anything and reviewing the variance is the entire
 * purpose of the screen.
 *
 * There is deliberately no reveal toggle here. An unaudited toggle is exactly what the
 * decision guards against, and recording the reveal needs a backend endpoint that does
 * not exist yet. The audited toggle belongs on `.count`, where the decision places it.
 */
const COUNTING_CLOSED = ['completed', 'approved'];

export default class CycleCountDetailsComponent extends Component {
    get countingClosed() {
        return COUNTING_CLOSED.includes(this.args.resource?.status);
    }

    /**
     * Lines with their variance, sorted so discrepancies surface first — a review reads
     * the exceptions, not the agreements.
     */
    get lines() {
        const items = [...(this.args.resource?.items ?? [])];

        return items
            .map((item) => {
                const expected = Number(item.expected_quantity) || 0;
                const counted = Number(item.counted_quantity) || 0;

                return {
                    item,
                    expected,
                    counted,
                    variance: Number(item.variance) || counted - expected,
                    hasDiscrepancy: Boolean(item.has_discrepancy),
                };
            })
            .sort((a, b) => Math.abs(b.variance) - Math.abs(a.variance));
    }

    get counting() {
        return this.lines.reduce(
            (totals, line) => {
                totals.lines += 1;
                totals.counted += line.item.counted_at || line.counted ? 1 : 0;
                totals.netVariance += line.variance;

                if (line.hasDiscrepancy || line.variance !== 0) {
                    totals.varianceLines += 1;
                }

                return totals;
            },
            { lines: 0, counted: 0, varianceLines: 0, netVariance: 0 }
        );
    }
}
