import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';

export default class FacilitiesZonesController extends Controller {
    @service hostRouter;
    @service notifications;
    @service store;

    /**
     * The vocabulary WarehouseZone::boot() documents and normalises to. The form
     * used to be a free-text box defaulting to 'storage', which is not one of
     * these — so every zone created through the console carried a type the domain
     * does not recognise.
     */
    zoneTypes = ['general', 'receiving', 'shipping', 'staging', 'returns', 'cold_storage'];

    @tracked newZone = { type: 'general', status: 'active' };

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    resetNewZone() {
        this.newZone = { type: 'general', status: 'active' };
    }

    @action setType(type) {
        this.newZone = { ...this.newZone, type };
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
                type: this.newZone.type ?? 'general',
                status: this.newZone.status ?? 'active',
                // capacity is nullable — a blank box means unknown, and coercing it
                // to 0 asserts the zone has no room at all
                capacity: this.newZone.capacity ? Number(this.newZone.capacity) : null,
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
