import Component from '@glimmer/component';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { isBlank } from '@ember/utils';

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

    @action addSection() {
        const section = this.store.createRecord('warehouse-section', { warehouse_uuid: this.warehouse.id });
        this.warehouse.sections.pushObject(section);
    }
}
