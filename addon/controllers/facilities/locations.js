import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';

export default class FacilitiesLocationsController extends Controller {
    @service hostRouter;
    @service notifications;
    @service store;

    @tracked newLocation = { type: 'storage', status: 'active', is_pickable: true, is_replenishable: true };

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    resetNewLocation() {
        this.newLocation = { type: 'storage', status: 'active', is_pickable: true, is_replenishable: true };
    }

    @action setWarehouse(warehouse) {
        this.newLocation = {
            ...this.newLocation,
            warehouse,
            warehouse_uuid: this.getRecordUuid(warehouse),
            zone: null,
            zone_uuid: null,
        };
    }

    @action setZone(zone) {
        this.newLocation = {
            ...this.newLocation,
            zone,
            zone_uuid: this.getRecordUuid(zone),
        };
    }

    @action async createLocation() {
        try {
            if (!this.newLocation.warehouse_uuid) {
                return this.notifications.warning('Select a warehouse for this bin location.');
            }

            if (!this.newLocation.bin_number) {
                return this.notifications.warning('Enter a bin number.');
            }

            const location = this.store.createRecord('bin-location', {
                warehouse_uuid: this.newLocation.warehouse_uuid,
                zone_uuid: this.newLocation.zone_uuid,
                bin_number: this.newLocation.bin_number,
                barcode: this.newLocation.barcode,
                type: this.newLocation.type ?? 'storage',
                status: this.newLocation.status ?? 'active',
                capacity: Number(this.newLocation.capacity ?? 0),
                priority: Number(this.newLocation.priority ?? 0),
                is_pickable: Boolean(this.newLocation.is_pickable),
                is_replenishable: Boolean(this.newLocation.is_replenishable),
            });

            await location.save();
            this.notifications.success('Bin location created.');
            this.resetNewLocation();
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
