import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object'

export default class SupplierFormPanelEditFormComponent extends Component {
    @service store;
    @service contextPanel;
    @tracked isIntegratedSupplier;
    @tracked isEditingCredentials = false;
    @tracked showAdvancedOptions = false;

    constructor() {
        super(...arguments);
        this.supplier = this.args.supplier;
        this.isIntegratedSupplier = this.supplier && this.supplier.type === 'integrated-supplier';
    }

    @action toggleCredentialsReset() {
        if (this.isEditingCredentials) {
            this.isEditingCredentials = false;
        } else {
            this.isEditingCredentials = true;
        }
    }

    @action toggleAdvancedOptions() {
        if (this.showAdvancedOptions) {
            this.showAdvancedOptions = false;
        } else {
            this.showAdvancedOptions = true;
        }
    }

    @action selectSupplierAddress(warehouse) {
        this.supplier.warehouse = warehouse;
        this.supplier.warehouse_uuid = warehouse.id;
    }

    @action async editAddress() {
        let warehouse;

        if (this.supplier.has_warehouse) {
            warehouse = await this.supplier.warehouse;
        } else {
            warehouse = this.store.createRecord('warehouse');
        }

        return this.contextPanel.focus(warehouse);
    }
}
