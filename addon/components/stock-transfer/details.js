import Component from '@glimmer/component';

/**
 * The movement figures for one stock transfer.
 *
 * SCREENS.md section F ties this screen to the `IN TRANSIT` quantity slot: what has
 * left the source but not yet arrived at the destination is the fact a transfer exists
 * to track, and the list only ever showed a single total quantity.
 *
 * Derived from the line items, which are a synchronous hasMany the table below already
 * renders — the same numbers the reader can see summed, with nothing to fall out of
 * step and no extra request.
 *
 * In transit is clamped at zero per line: receiving more than was sent would otherwise
 * produce a negative that silently cancels another line's outstanding quantity out of
 * the total.
 */
export default class StockTransferDetailsComponent extends Component {
    /**
     * The line rows, each carrying its own in-transit figure.
     *
     * Computed here rather than with a `sub` helper in the template for two reasons:
     * ember-math-helpers is a dependency but `sub` has never been used in this engine,
     * and an unresolved helper fails at runtime rather than at lint; and `sub` would
     * render a negative for an over-received line while the total below clamps at
     * zero, so the column and its total would disagree.
     */
    get lines() {
        const items = this.args.resource?.items ?? [];

        return items.map((item) => {
            const sent = Number(item.quantity) || 0;
            const received = Number(item.quantity_received) || 0;

            return { item, sent, received, inTransit: Math.max(sent - received, 0) };
        });
    }

    get movement() {
        const items = this.args.resource?.items ?? [];

        return items.reduce(
            (totals, item) => {
                const sent = Number(item.quantity) || 0;
                const received = Number(item.quantity_received) || 0;

                totals.lines += 1;
                totals.sent += sent;
                totals.received += received;
                totals.inTransit += Math.max(sent - received, 0);

                if (received >= sent && sent > 0) {
                    totals.linesComplete += 1;
                }

                return totals;
            },
            { lines: 0, linesComplete: 0, sent: 0, received: 0, inTransit: 0 }
        );
    }
}
