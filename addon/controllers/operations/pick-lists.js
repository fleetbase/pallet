import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class OperationsPickListsController extends Controller {
    @service fetch;
    @service hostRouter;
    @service notifications;

    async updatePickList(pickList, actionName, payload = {}) {
        try {
            await this.fetch.post(`pick-lists/${pickList.public_id ?? pickList.id}/${actionName}`, payload, { namespace: 'pallet/int/v1' });
            this.notifications.success(`Pick list ${this.actionLabel(actionName)} successfully.`);
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action startPickList(pickList) {
        return this.updatePickList(pickList, 'start');
    }

    @action assignPickList(pickList) {
        return this.updatePickList(pickList, 'assign', { assigned_to_uuid: pickList.assigned_to_uuid });
    }

    @action completePickList(pickList) {
        return this.updatePickList(pickList, 'complete');
    }

    actionLabel(actionName) {
        if (actionName === 'start') {
            return 'started';
        }

        if (actionName === 'assign') {
            return 'assigned';
        }

        return `${actionName}d`;
    }
}
