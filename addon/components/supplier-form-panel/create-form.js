import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { isArray } from '@ember/array';
import apiUrl from '@fleetbase/ember-core/utils/api-url';
import contextComponentCallback from '../../utils/context-component-callback';

export default class SupplierFormPanelCreateFormComponent extends Component {
    /**
     * @service store
     */
    @service store;

    /**
     * @service fetch
     */
    @service fetch;

    /**
     * @service contextPanel
     */
    @service contextPanel;
    /**
     * State of whether editing credentials is enabled.
     * @type {Boolean}
     */
    @tracked isEditingCredentials = false;

    /**
     * State of whether to show advanced options for integrated supplier.
     * @type {Boolean}
     */
    @tracked showAdvancedOptions = false;

    /**
     * The selectable types of suppliers.
     * @type {Array}
     */
    @tracked supplierTypes = [
        { label: 'Choose a integrated supplier', value: 'integrated-supplier' },
        { label: 'Create a custom supplier', value: 'supplier' },
    ];

    /**
     * The selected type of supplier being created or edited.
     * @type {String}
     */
    @tracked selectedSupplierType = this.supplierTypes[1];

    /**
     * The supported integrated suppliers.
     * @type {Array}
     */
    @tracked supportedIntegratedSuppliers = [];

    /**
     * The selected integrated supplier provider.
     * @type {Object}
     */
    @tracked selectedIntegratedSupplier;

    constructor() {
        super(...arguments);
        this.supplier = this.args.supplier;
        this.fetchSupportedIntegratedSuppliers();
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

    @action onSelectSupplierType(selectedSupplierType) {
        this.selectedSupplierType = selectedSupplierType;
    }

    @action onSelectIntegratedSupplier(integratedSupplier) {
        this.selectedIntegratedSupplier = integratedSupplier;
        const { credential_params, option_params } = integratedSupplier;

        // create credentials object
        const credentials = {};
        if (isArray(integratedSupplier.credential_params)) {
            for (let i = 0; i < integratedSupplier.credential_params.length; i++) {
                const param = integratedSupplier.credential_params.objectAt(i);
                credentials[param] = null;
            }
        }

        // create options object
        const options = {};
        if (isArray(integratedSupplier.option_params)) {
            for (let i = 0; i < integratedSupplier.option_params.length; i++) {
                const param = integratedSupplier.option_params.objectAt(i);
                options[param.key] = null;
            }
        }

        const supplier = this.store.createRecord('integrated-supplier', {
            provider: integratedSupplier.code,
            webhook_url: apiUrl(`listeners/${integratedSupplier.code}`),
            credentials: {},
            options: {},
            credential_params,
            option_params,
        });

        this.supplier = supplier;

        // trigger callback
        contextComponentCallback(this, 'onSupplierChanged', supplier);
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

        return this.contextPanel.focus(warehouse, 'editing', {
            onAfterSave: (warehouse) => {
                this.supplier.warehouse = warehouse;
                this.contextPanel.clear();
            },
        });
    }

    /**
     * Fetches the supported integrated suppliers.
     *
     * @returns {Promise}
     */
    fetchSupportedIntegratedSuppliers() {
        return this.fetch.get('integrated-suppliers/supported').then((supportedIntegratedSuppliers) => {
            this.supportedIntegratedSuppliers = supportedIntegratedSuppliers;
        });
    }
}
