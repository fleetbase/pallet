import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';

export default class OperationsCycleCountsController extends Controller {
    @service currentUser;
    @service fetch;
    @service hostRouter;
    @service notifications;
    @service store;

    @tracked newCycleCount = { type: 'standard' };

    resetNewCycleCount() {
        this.newCycleCount = { type: 'standard' };
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    get currentUserUuid() {
        return this.currentUser.user?.uuid ?? this.currentUser.user?.id ?? this.currentUser.uuid ?? this.currentUser.id;
    }

    @action setCycleCountWarehouse(warehouse) {
        this.newCycleCount = {
            ...this.newCycleCount,
            warehouse,
            warehouse_uuid: this.getRecordUuid(warehouse),
            zone: null,
            zone_uuid: null,
        };
    }

    @action setCycleCountZone(zone) {
        this.newCycleCount = {
            ...this.newCycleCount,
            zone,
            zone_uuid: this.getRecordUuid(zone),
        };
    }

    @action async createCycleCount() {
        try {
            if (!this.newCycleCount.warehouse_uuid) {
                return this.notifications.warning('Select a warehouse for this cycle count.');
            }

            const cycleCount = this.store.createRecord('cycle-count', {
                warehouse_uuid: this.newCycleCount.warehouse_uuid,
                zone_uuid: this.newCycleCount.zone_uuid,
                type: this.newCycleCount.type ?? 'standard',
                scheduled_at: this.newCycleCount.scheduled_at,
                notes: this.newCycleCount.notes,
                status: 'pending',
            });
            await cycleCount.save();
            this.notifications.success('Cycle count created.');
            this.resetNewCycleCount();
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

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

    @action async recordCycleCountItem(item) {
        const countedQuantity = Number(item.counted_quantity ?? 0);

        if (countedQuantity < 0) {
            return this.notifications.warning('Enter a counted quantity of zero or greater.');
        }

        try {
            await this.fetch.post(
                `cycle-count-items/${item.public_id ?? item.id}/record-count`,
                {
                    counted_quantity: countedQuantity,
                    counted_by_uuid: this.currentUserUuid,
                },
                { namespace: 'pallet/int/v1' }
            );
            this.notifications.success('Cycle count item recorded.');
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    actionLabel(actionName) {
        return actionName === 'start' ? 'started' : `${actionName}d`;
    }
}
