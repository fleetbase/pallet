import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
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

    @tracked overlayContextApi;

    constructor() {
        super(...arguments);
        this.warehouse = this.args.warehouse;
    }

    @action setOverlayContext(overlayContextApi) {
        this.overlayContextApi = overlayContextApi;
    }

    @action openEditor() {
        console.log('openEditor() #overlayContextApi', this.overlayContextApi);
        if (this.overlayContextApi) {
            this.overlayContextApi.open();
        }
    }

    @action closeEditor() {
        if (this.overlayContextApi) {
            this.overlayContextApi.close();
        }
    }

    @action addSection() {
        const section = this.store.createRecord('warehouse-section', { warehouse_uuid: this.warehouse.id });
        this.warehouse.sections.pushObject(section);
    }

    @action addAisle(section) {
        const aisle = this.store.createRecord('warehouse-aisle', { section: section });

        if (!section.aisles) {
            section.set('aisles', []);
        }

        section.aisles.pushObject(aisle);
    }

    @action addRack(aisle) {
        const rack = this.store.createRecord('warehouse-rack', { aisle: aisle });

        if (!aisle.racks) {
            aisle.set('racks', []);
        }

        aisle.racks.pushObject(rack);
    }

    @action addBin(rack) {
        const bin = this.store.createRecord('warehouse-bin', { rack: rack });

        if (!rack.bins) {
            rack.set('bins', []);
        }

        rack.bins.pushObject(bin);
    }

    @action addDock() {
        const dock = this.store.createRecord('warehouse-dock', { warehouse_uuid: this.warehouse.id });
        this.warehouse.docks.pushObject(dock);
    }

    @action removeDock(dock) {
        dock.destroyRecord();
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
}
