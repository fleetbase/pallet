import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class OperationsCycleCountsController extends Controller {
    @service fetch;
    @service hostRouter;
    @service notifications;

    async updateCycleCount(cycleCount, actionName) {
        try {
            await this.fetch.post(`cycle-counts/${cycleCount.public_id ?? cycleCount.id}/${actionName}`, {}, { namespace: 'pallet/int/v1' });
            this.notifications.success(`Cycle count ${this.actionLabel(actionName)} successfully.`);
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action startCycleCount(cycleCount) {
        return this.updateCycleCount(cycleCount, 'start');
    }

    @action completeCycleCount(cycleCount) {
        return this.updateCycleCount(cycleCount, 'complete');
    }

    @action approveCycleCount(cycleCount) {
        return this.updateCycleCount(cycleCount, 'approve');
    }

    actionLabel(actionName) {
        return actionName === 'start' ? 'started' : `${actionName}d`;
    }
}
