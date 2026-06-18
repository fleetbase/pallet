import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';

export default class FacilitiesZonesController extends Controller {
    @service hostRouter;
    @service notifications;
    @service store;

    @tracked newZone = { type: 'storage', status: 'active' };

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    resetNewZone() {
        this.newZone = { type: 'storage', status: 'active' };
    }

    @action setWarehouse(warehouse) {
        this.newZone = {
            ...this.newZone,
            warehouse,
            warehouse_uuid: this.getRecordUuid(warehouse),
        };
    }

    @action async createZone() {
        try {
            if (!this.newZone.warehouse_uuid) {
                return this.notifications.warning('Select a warehouse for this zone.');
            }

            if (!this.newZone.name) {
                return this.notifications.warning('Enter a zone name.');
            }

            const zone = this.store.createRecord('warehouse-zone', {
                warehouse_uuid: this.newZone.warehouse_uuid,
                name: this.newZone.name,
                code: this.newZone.code,
                type: this.newZone.type ?? 'storage',
                status: this.newZone.status ?? 'active',
                capacity: Number(this.newZone.capacity ?? 0),
                temperature_controlled: Boolean(this.newZone.temperature_controlled),
            });

            await zone.save();
            this.notifications.success('Warehouse zone created.');
            this.resetNewZone();
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
