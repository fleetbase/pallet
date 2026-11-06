import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';

export default class LocationsIndexDetailsController extends Controller {
    @service hostRouter;

    @tracked tabs = [
        {
            route: 'facilities.locations.index.details.index',
            label: 'Overview',
        },
    ];

    get actionButtons() {
        return [
            {
                icon: 'pencil',
                fn: () => this.hostRouter.transitionTo('console.pallet.facilities.locations.index.edit', this.model),
            },
        ];
    }
}
