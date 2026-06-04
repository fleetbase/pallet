import ResourceActionService from '@fleetbase/ember-core/services/resource-action';
import { action } from '@ember/object';

export default class WarehouseActionsService extends ResourceActionService {
    constructor() {
        super(...arguments);

        this.initialize('warehouse', {
            modelNamePath: 'name',
            defaultAttributes: {
                type: 'pallet-warehouse',
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
        view: (warehouse) => this.transitionTo('facilities.warehouses.index.details', warehouse),
        edit: (warehouse) => this.transitionTo('facilities.warehouses.index.edit', warehouse),
        create: () => this.transitionTo('facilities.warehouses.index.new'),
    };

    panel = {
        create: (attributes = {}) => {
            const warehouse = this.createNewInstance(attributes);

            return this.resourceContextPanel.open({
                content: 'warehouse/form',
                title: 'Create a new warehouse',
                useDefaultSaveTask: true,
                saveOptions: {
                    callback: this.refresh,
                },
                warehouse,
            });
        },
        edit: async (warehouse) => {
            if (warehouse?.meta?._index_resource) {
                await warehouse.reload();
            }

            return this.resourceContextPanel.open({
                content: 'warehouse/form',
                title: `Edit ${this.getRecordName(warehouse)}`,
                useDefaultSaveTask: true,
                warehouse,
            });
        },
        view: async (warehouse) => {
            if (warehouse?.meta?._index_resource) {
                await warehouse.reload();
            }

            return this.resourceContextPanel.open({
                warehouse,
                tabs: [
                    {
                        label: 'Overview',
                        component: 'warehouse/details',
                    },
                ],
            });
        },
    };

    modal = {
        create: (attributes = {}, options = {}, saveOptions = {}) => {
            const warehouse = this.createNewInstance(attributes);

            return this.modalsManager.show('modals/resource', {
                resource: warehouse,
                title: 'Create a new warehouse',
                acceptButtonText: 'Create Warehouse',
                component: 'warehouse/form',
                confirm: (modal) => this.modalTask.perform(modal, 'saveTask', warehouse, { refresh: true, ...saveOptions }),
                ...options,
            });
        },
        edit: async (warehouse, options = {}, saveOptions = {}) => {
            if (warehouse?.meta?._index_resource) {
                await warehouse.reload();
            }

            return this.modalsManager.show('modals/resource', {
                resource: warehouse,
                title: `Edit ${this.getRecordName(warehouse)}`,
                acceptButtonText: 'Save Changes',
                saveButtonIcon: 'save',
                component: 'warehouse/form',
                confirm: (modal) => this.modalTask.perform(modal, 'saveTask', warehouse, { refresh: true, ...saveOptions }),
                ...options,
            });
        },
        view: async (warehouse, options = {}) => {
            if (warehouse?.meta?._index_resource) {
                await warehouse.reload();
            }

            return this.modalsManager.show('modals/resource', {
                resource: warehouse,
                title: this.getRecordName(warehouse),
                component: 'warehouse/details',
                ...options,
            });
        },
    };

    @action locate(warehouse, options = {}) {
        const { latitude, longitude, location } = warehouse;

        return this.modalsManager.show('modals/point-map', {
            title: `Location of ${warehouse.name}`,
            acceptButtonText: 'Done',
            hideDeclineButton: true,
            resource: warehouse,
            latitude,
            longitude,
            location: location ?? [latitude, longitude],
            ...options,
        });
    }
}
