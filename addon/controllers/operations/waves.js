import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class OperationsWavesController extends Controller {
    @service fetch;
    @service hostRouter;
    @service notifications;

    async updateWave(wave, actionName) {
        try {
            await this.fetch.post(`waves/${wave.public_id ?? wave.id}/${actionName}`, {}, { namespace: 'pallet/int/v1' });
            this.notifications.success(`Wave ${this.actionLabel(actionName)} successfully.`);
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action startWave(wave) {
        return this.updateWave(wave, 'start');
    }

    @action releaseWave(wave) {
        return this.updateWave(wave, 'release');
    }

    @action completeWave(wave) {
        return this.updateWave(wave, 'complete');
    }

    actionLabel(actionName) {
        return actionName === 'start' ? 'started' : `${actionName}d`;
    }
}
