import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class OperationsCycleCountsCountController extends Controller {
    @service hostRouter;

    @action transitionBack() {
        return this.hostRouter.transitionTo('console.pallet.operations.cycle-counts.details', this.model.public_id);
    }
}
