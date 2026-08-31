import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';

export default class WarehousesIndexDetailsController extends Controller {
    @service hostRouter;

    @tracked tabs = [
        {
            route: 'facilities.warehouses.index.details.index',
            label: 'Overview',
        },
    ];

    get actionButtons() {
        return [
            {
                icon: 'pencil',
                fn: () => this.hostRouter.transitionTo('console.pallet.facilities.warehouses.index.edit', this.model),
            },
        ];
    }
}
