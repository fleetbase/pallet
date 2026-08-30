import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

/**
 * Loading docks for one warehouse.
 *
 * The warehouse record already carries a `docks` hasMany, but the detail route does not
 * side-load it, so reading `@resource.docks` would give an empty array rather than a
 * fetch. Querying explicitly keeps this panel honest about what it is showing.
 *
 * Generator syntax, not `task(async () => {})` — the async-arrow form is not compiled by
 * Babel in this engine.
 */
export default class WarehouseDocksComponent extends Component {
    @service store;
    @service notifications;

    @tracked docks = [];

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
            this.docks = yield this.store.query('warehouse-dock', { warehouse, sort: 'dock_number', limit: 50 });
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
