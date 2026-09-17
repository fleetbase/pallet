import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';

export default class ProductsIndexDetailsController extends Controller {
    @service hostRouter;

    @tracked tabs = [
        {
            route: 'catalog.products.index.details.index',
            label: 'Overview',
        },
    ];

    get actionButtons() {
        return [
            {
                icon: 'pencil',
                fn: () => this.hostRouter.transitionTo('console.pallet.catalog.products.index.edit', this.model),
            },
        ];
    }
}
