import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';

export default class FacilitiesLocationsController extends Controller {
    @service hostRouter;
    @service notifications;
    @service store;

    /**
     * The vocabulary BinLocation::boot() documents and normalises to. As with
     * zones, the form was a free-text box defaulting to 'storage', which is not
     * one of these.
     */
    binTypes = ['standard', 'bulk', 'pallet', 'shelf'];

    @tracked newLocation = { type: 'standard', status: 'active', is_pickable: true, is_replenishable: true };

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    resetNewLocation() {
        this.newLocation = { type: 'standard', status: 'active', is_pickable: true, is_replenishable: true };
    }

    @action setType(type) {
        this.newLocation = { ...this.newLocation, type };
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
                type: this.newLocation.type ?? 'standard',
                status: this.newLocation.status ?? 'active',
                // capacity is nullable — a blank box means unknown, not "no room"
                capacity: this.newLocation.capacity ? Number(this.newLocation.capacity) : null,
                // priority orders picking and the column defaults to 5; sending 0
                // silently overrode that for every bin created through the console
                priority: this.newLocation.priority ? Number(this.newLocation.priority) : undefined,
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
