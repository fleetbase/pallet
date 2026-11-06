import ResourceActionService from '@fleetbase/ember-core/services/resource-action';

export default class SupplierActionsService extends ResourceActionService {
    constructor() {
        super(...arguments);

        this.initialize('supplier', {
            modelNamePath: 'name',
            defaultAttributes: {
                status: 'active',
                meta: {},
            },
            fetchOptions: {
                namespace: 'pallet/int/v1',
            },
            permissionPrefix: 'pallet',
            mountPrefix: 'console.pallet',
        });
    }

    transition = {
        view: (supplier) => this.transitionTo('catalog.suppliers.index.details', supplier),
        edit: (supplier) => this.transitionTo('catalog.suppliers.index.edit', supplier),
        create: () => this.transitionTo('catalog.suppliers.index.new'),
    };

    panel = {
        create: (attributes = {}) => {
            const supplier = this.createNewInstance(attributes);

            return this.resourceContextPanel.open({
                content: 'supplier/form',
                title: 'Create a new supplier',
                useDefaultSaveTask: true,
                saveOptions: {
                    callback: this.refresh,
                },
                supplier,
            });
        },
        edit: async (supplier) => {
            if (supplier?.meta?._index_resource) {
                await supplier.reload();
            }

            return this.resourceContextPanel.open({
                content: 'supplier/form',
                title: `Edit ${this.getRecordName(supplier)}`,
                useDefaultSaveTask: true,
                supplier,
            });
        },
        view: async (supplier) => {
            if (supplier?.meta?._index_resource) {
                await supplier.reload();
            }

            return this.resourceContextPanel.open({
                supplier,
                tabs: [
                    {
                        label: 'Overview',
                        component: 'supplier/details',
                    },
                ],
            });
        },
    };

    modal = {
        create: (attributes = {}, options = {}, saveOptions = {}) => {
            const supplier = this.createNewInstance(attributes);

            return this.modalsManager.show('modals/resource', {
                resource: supplier,
                title: 'Create a new supplier',
                acceptButtonText: 'Create Supplier',
                component: 'supplier/form',
                confirm: (modal) => this.modalTask.perform(modal, 'saveTask', supplier, { refresh: true, ...saveOptions }),
                ...options,
            });
        },
        edit: async (supplier, options = {}, saveOptions = {}) => {
            if (supplier?.meta?._index_resource) {
                await supplier.reload();
            }

            return this.modalsManager.show('modals/resource', {
                resource: supplier,
                title: `Edit ${this.getRecordName(supplier)}`,
                acceptButtonText: 'Save Changes',
                saveButtonIcon: 'save',
                component: 'supplier/form',
                confirm: (modal) => this.modalTask.perform(modal, 'saveTask', supplier, { refresh: true, ...saveOptions }),
                ...options,
            });
        },
        view: async (supplier, options = {}) => {
            if (supplier?.meta?._index_resource) {
                await supplier.reload();
            }

            return this.modalsManager.show('modals/resource', {
                resource: supplier,
                title: this.getRecordName(supplier),
                component: 'supplier/details',
                ...options,
            });
        },
    };
}
