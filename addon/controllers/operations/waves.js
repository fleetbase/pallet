import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';

export default class OperationsWavesController extends Controller {
    @service fetch;
    @service hostRouter;
    @service notifications;
    @service store;

    @tracked newWave = { type: 'standard', priority: 5 };

    resetNewWave() {
        this.newWave = { type: 'standard', priority: 5 };
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    @action setWaveWarehouse(warehouse) {
        this.newWave = {
            ...this.newWave,
            warehouse,
            warehouse_uuid: this.getRecordUuid(warehouse),
        };
    }

    @action async createWave() {
        try {
            if (!this.newWave.warehouse_uuid) {
                return this.notifications.warning('Select a warehouse for this wave.');
            }

            const priority = Number(this.newWave.priority ?? 5);
            if (!priority || priority <= 0) {
                return this.notifications.warning('Enter a priority greater than zero.');
            }

            const wave = this.store.createRecord('wave', {
                warehouse_uuid: this.newWave.warehouse_uuid,
                type: this.newWave.type ?? 'standard',
                priority,
                scheduled_at: this.newWave.scheduled_at,
                notes: this.newWave.notes,
                status: 'pending',
            });
            await wave.save();
            this.notifications.success('Wave created.');
            this.resetNewWave();
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

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
