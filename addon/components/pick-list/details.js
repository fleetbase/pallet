import Component from '@glimmer/component';

/**
 * One pick list, as a route the picker walks.
 *
 * SCREENS.md §F describes the lines as being "listed below in walk order" — that order
 * is `sequence_number`, and it is the whole point of a pick list: the sequence is what
 * turns a set of lines into a route through the building. The API returns items in
 * whatever order the database hands back, so the sort happens here.
 */
export default class PickListDetailsComponent extends Component {
    /**
     * Lines in walk order, each carrying its own outstanding quantity.
     *
     * Outstanding is clamped at zero for the same reason the transfer document clamps
     * in transit: an over-pick would otherwise print a negative that cancels another
     * line's shortfall out of the total below.
     */
    get lines() {
        const items = [...(this.args.resource?.items ?? [])];

        items.sort((a, b) => (Number(a.sequence_number) || 0) - (Number(b.sequence_number) || 0));

        return items.map((item) => {
            const requested = Number(item.quantity_requested) || 0;
            const picked = Number(item.quantity_picked) || 0;

            return { item, requested, picked, outstanding: Math.max(requested - picked, 0) };
        });
    }

    get picking() {
        return this.lines.reduce(
            (totals, line) => {
                totals.lines += 1;
                totals.requested += line.requested;
                totals.picked += line.picked;
                totals.outstanding += line.outstanding;

                if (line.picked >= line.requested && line.requested > 0) {
                    totals.linesComplete += 1;
                }

                return totals;
            },
            { lines: 0, linesComplete: 0, requested: 0, picked: 0, outstanding: 0 }
        );
    }
}
