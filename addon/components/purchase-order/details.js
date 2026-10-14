import Component from '@glimmer/component';

/**
 * The receiving figures for one purchase order.
 *
 * SCREENS.md §E names hiding "outstanding" as the must-never for this screen — it is
 * the number a receiving clerk works from. The panel had compressed received and
 * ordered into a single "50 / 50" cell precisely because it used to live in a 600px
 * side panel and eight columns scrolled off the edge. The document view removed that
 * constraint, so the figures are separate again and outstanding is stated rather than
 * left to be worked out.
 *
 * Derived from the line items rather than fetched: `items` is a synchronous hasMany
 * that the line-item table already renders, so the totals are the same numbers the
 * reader can see summed, with no extra request and nothing to fall out of step.
 *
 * `outstanding_quantity` comes from the server, but a line whose received quantity
 * exceeds what was ordered would make it negative; over-receipt is real (SCREENS.md
 * §E treats it as a warning, not an error), and a negative outstanding would silently
 * cancel out another line's shortfall in the total. Clamped at zero, with over-receipt
 * counted separately.
 */
export default class PurchaseOrderDetailsComponent extends Component {
    get receiving() {
        const items = this.args.resource?.items ?? [];

        return items.reduce(
            (totals, item) => {
                const ordered = Number(item.quantity) || 0;
                const received = Number(item.quantity_received) || 0;

                totals.lines += 1;
                totals.ordered += ordered;
                totals.received += received;
                totals.outstanding += Math.max(ordered - received, 0);
                totals.over += Math.max(received - ordered, 0);

                if (received >= ordered && ordered > 0) {
                    totals.linesComplete += 1;
                }

                return totals;
            },
            { lines: 0, linesComplete: 0, ordered: 0, received: 0, outstanding: 0, over: 0 }
        );
    }
}
