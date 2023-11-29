import Component from '@glimmer/component';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class WarehouseEditorComponent extends Component {
    /**
     * @service store
     */
    @service store;

    /**
     * @service fetch
     */
    @service fetch;

    /**
     * @service notifications
     */
    @service notifications;

    constructor() {
        super(...arguments);
        this.warehouse = this.args.warehouse;
    }

    @action removeSection(section) {
        section.destroyRecord();
    }

    @action removeAisle(aisle) {
        aisle.destroyRecord();
    }

    @action removeRack(rack) {
        rack.destroyRecord();
    }

    @action removeBin(bin) {
        bin.destroyRecord();
    }

    @action addSection() {
        const section = this.store.createRecord('warehouse-section', { warehouse_uuid: this.warehouse.id });
        this.warehouse.sections.pushObject(section);
    }

    @action addAisle(section) {
        if (section && section.uuid) {
            const aisle = this.store.createRecord('warehouse-aisle', {
                section: section,
            });

            if (!section.aisles) {
                section.set('aisles', []);
            }

            section.aisles.pushObject(aisle);
        }
    }

    @action addRacks(aisle) {
        if (aisle && aisle.uuid) {
            const rack = this.store.createRecord('warehouse-rack', {
                aisle: aisle,
            });

            if (!aisle.racks) {
                aisle.set('racks', []);
            }

            aisle.racks.pushObject(rack);
        }
    }

    @action addBins(rack) {
        if (rack && rack.uuid) {
            const bin = this.store.createRecord('warehouse-bin', {
                rack: rack,
            });

            if (!rack.bins) {
                rack.set('bins', []);
            }

            rack.bins.pushObject(bin);
        }
    }
}
