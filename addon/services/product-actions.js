import ResourceActionService from '@fleetbase/ember-core/services/resource-action';

export default class ProductActionsService extends ResourceActionService {
    constructor() {
        super(...arguments);

        this.initialize('pallet-product', {
            modelNamePath: 'name',
            defaultAttributes: {
                status: 'active',
                has_variants: false,
            },
            fetchOptions: {
                namespace: 'pallet/int/v1',
            },
            // crud.bulkDelete derives its path from the model name, so `pallet-product`
            // becomes `pallet-products/bulk-delete` and 404s. The route is `products`,
            // which is what the adapter's pathForType already strips the prefix to
            // reach; the service just does not consult it.
            bulkDeleteOptions: {
                actionPath: 'products/bulk-delete',
            },
            permissionPrefix: 'pallet',
            mountPrefix: 'console.pallet',
        });
    }

    transition = {
        view: (product) => this.transitionTo('catalog.products.index.details', product),
        edit: (product) => this.transitionTo('catalog.products.index.edit', product),
        create: () => this.transitionTo('catalog.products.index.new'),
    };

    panel = {
        create: (attributes = {}) => {
            const product = this.createNewInstance(attributes);

            return this.resourceContextPanel.open({
                content: 'product/form',
                title: 'Create a new product',
                useDefaultSaveTask: true,
                saveOptions: {
                    callback: this.refresh,
                },
                product,
            });
        },
        edit: async (product) => {
            if (product?.meta?._index_resource) {
                await product.reload();
            }

            return this.resourceContextPanel.open({
                content: 'product/form',
                title: `Edit ${this.getRecordName(product)}`,
                useDefaultSaveTask: true,
                product,
            });
        },
        view: async (product) => {
            if (product?.meta?._index_resource) {
                await product.reload();
            }

            return this.resourceContextPanel.open({
                product,
                tabs: [
                    {
                        label: 'Overview',
                        component: 'product/details',
                    },
                ],
            });
        },
    };

    modal = {
        create: (attributes = {}, options = {}, saveOptions = {}) => {
            const product = this.createNewInstance(attributes);

            return this.modalsManager.show('modals/resource', {
                resource: product,
                title: 'Create a new product',
                acceptButtonText: 'Create Product',
                component: 'product/form',
                confirm: (modal) => this.modalTask.perform(modal, 'saveTask', product, { refresh: true, ...saveOptions }),
                ...options,
            });
        },
        edit: async (product, options = {}, saveOptions = {}) => {
            if (product?.meta?._index_resource) {
                await product.reload();
            }

            return this.modalsManager.show('modals/resource', {
                resource: product,
                title: `Edit ${this.getRecordName(product)}`,
                acceptButtonText: 'Save Changes',
                saveButtonIcon: 'save',
                component: 'product/form',
                confirm: (modal) => this.modalTask.perform(modal, 'saveTask', product, { refresh: true, ...saveOptions }),
                ...options,
            });
        },
        view: async (product, options = {}) => {
            if (product?.meta?._index_resource) {
                await product.reload();
            }

            return this.modalsManager.show('modals/resource', {
                resource: product,
                title: this.getRecordName(product),
                component: 'product/details',
                ...options,
            });
        },
    };
}
