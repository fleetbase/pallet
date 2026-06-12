import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class OperationsTransfersController extends Controller {
    @service fetch;
    @service hostRouter;
    @service notifications;

    async updateTransfer(transfer, actionName) {
        try {
            await this.fetch.post(`stock-transfers/${transfer.public_id ?? transfer.id}/${actionName}`, {}, { namespace: 'pallet/int/v1' });
            this.notifications.success(`Stock transfer ${this.actionLabel(actionName)} successfully.`);
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action approveTransfer(transfer) {
        return this.updateTransfer(transfer, 'approve');
    }

    @action shipTransfer(transfer) {
        return this.updateTransfer(transfer, 'ship');
    }

    @action receiveTransfer(transfer) {
        return this.updateTransfer(transfer, 'receive');
    }

    @action cancelTransfer(transfer) {
        return this.updateTransfer(transfer, 'cancel');
    }

    actionLabel(actionName) {
        if (actionName === 'ship') {
            return 'shipped';
        }

        if (actionName === 'cancel') {
            return 'cancelled';
        }

        return `${actionName}d`;
    }
}
