import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class InventoryAdjustmentsController extends Controller {
    @service hostRouter;

    @action createAdjustment() {
        return this.hostRouter.transitionTo('console.pallet.inventory.index.new-stock-adjustment');
    }
}
