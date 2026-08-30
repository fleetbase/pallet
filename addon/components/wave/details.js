import Component from '@glimmer/component';

/**
 * The pick-list progress for one wave.
 *
 * The wave carries `total_pick_lists` and `completed_pick_lists` from the server, but
 * they count lists, not the work inside them: a wave can read 0 / 2 while most of its
 * lines are already picked. The line totals come from the pick lists themselves, which
 * are a synchronous hasMany the panel below already renders.
 */
export default class WaveDetailsComponent extends Component {
    get picking() {
        const lists = this.args.resource?.pickLists ?? [];

        return lists.reduce(
            (totals, list) => {
                const total = Number(list.total_items) || 0;
                const picked = Number(list.picked_items) || 0;

                totals.lists += 1;
                totals.lines += total;
                totals.picked += picked;
                totals.outstanding += Math.max(total - picked, 0);

                if (list.status === 'completed') {
                    totals.listsComplete += 1;
                }

                return totals;
            },
            { lists: 0, listsComplete: 0, lines: 0, picked: 0, outstanding: 0 }
        );
    }
}
