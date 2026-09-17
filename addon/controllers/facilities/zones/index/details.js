import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';

export default class ZonesIndexDetailsController extends Controller {
    @service hostRouter;

    @tracked tabs = [
        {
            route: 'facilities.zones.index.details.index',
            label: 'Overview',
        },
    ];

    get actionButtons() {
        return [
            {
                icon: 'pencil',
                fn: () => this.hostRouter.transitionTo('console.pallet.facilities.zones.index.edit', this.model),
            },
        ];
    }
}
