import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

/**
 * The zones inside one warehouse.
 *
 * SCREENS.md §C wants zones and bins reached from the warehouse rather than from the
 * top-level nav, "which would lose the warehouse context". This adds that path; it does
 * not remove the existing top-level Zones and Locations routes, which work — see
 * DESIGN_DEVIATIONS. Adding a route is reversible, deleting working nav is not, and the
 * choice is Ron's.
 *
 * Zones, not bins: §C's must-never for facilities is showing a warehouse's bins as a
 * flat 4,000-row list. Bins are reached through their zone.
 *
 * Generator syntax, not `task(async () => {})` — the async-arrow form is not compiled by
 * Babel in this engine.
 */
export default class WarehouseStructureComponent extends Component {
    @service store;
    @service notifications;

    @tracked zones = [];

    get warehouseUuid() {
        return this.args.resource?.uuid ?? this.args.resource?.id;
    }

    @task({ drop: true })
    *load() {
        const warehouse = this.warehouseUuid;

        if (!warehouse) {
            return;
        }

        try {
            this.zones = yield this.store.query('warehouse-zone', { warehouse, sort: 'name', limit: 50 });
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
