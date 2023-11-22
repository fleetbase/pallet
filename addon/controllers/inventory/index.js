import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class InventoryIndexController extends Controller {
    /**
     * Inject the `notifications` service
     *
     * @var {Service}
     */
    @service notifications;

    /**
     * Inject the `modals-manager` service
     *
     * @var {Service}
     */
    @service modalsManager;

    /**
     * Inject the `store` service
     *
     * @var {Service}
     */
    @service store;

    /**
     * Inject the `fetch` service
     *
     * @var {Service}
     */
    @service fetch;

    /**
     * Inject the `filters` service
     *
     * @var {Service}
     */
    @service filters;

    /**
     * Inject the `hostRouter` service
     *
     * @var {Service}
     */
    @service hostRouter;

    /**
     * Inject the `crud` service
     *
     * @var {Service}
     */
    @service crud;

    /**
     * Queryable parameters for this controller's model
     *
     * @var {Array}
     */
    queryParams = ['page', 'limit', 'sort', 'product', 'warehouse', 'batch'];

    /**
     * The current page of data being viewed
     *
     * @var {Integer}
     */
    @tracked page = 1;

    /**
     * The maximum number of items to show per page
     *
     * @var {Integer}
     */
    @tracked limit;

    /**
     * The param to sort the data on, the param with prepended `-` is descending
     *
     * @var {String}
     */
    @tracked sort = '-created_at';

    /**
     * The filterable param `sku`
     *
     * @var {String}
     */
    @tracked sku;

    /**
     * The filterable param `status`
     *
     * @var {String}
     */
    @tracked status;

    /**
     * All columns applicable for orders
     *
     * @var {Array}
     */
    @tracked columns = [
        {
            label: 'Product',
            valuePath: 'product.name',
            width: '200px',
            cellComponent: 'table/cell/anchor',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/string',
        },
        {
            label: 'Quantity',
            valuePath: 'quantity',
            width: '200px',
        },
        {
            label: 'Warehouse',
            valuePath: 'warehouse.address',
            width: '120px',
            cellComponent: 'click-to-copy',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/string',
        },
        {
            label: 'Batch',
            valuePath: 'batch.name',
            width: '120px',
            cellComponent: 'click-to-copy',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/string',
        },
        {
            label: 'Created At',
            valuePath: 'createdAt',
            sortParam: 'created_at',
            width: '10%',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/date',
        },
        {
            label: 'Updated At',
            valuePath: 'updatedAt',
            sortParam: 'updated_at',
            width: '10%',
            resizable: true,
            sortable: true,
            hidden: true,
            filterable: true,
            filterComponent: 'filter/date',
        },
        {
            label: '',
            cellComponent: 'table/cell/dropdown',
            ddButtonText: false,
            ddButtonIcon: 'ellipsis-h',
            ddButtonIconPrefix: 'fas',
            ddMenuLabel: 'Inventory Actions',
            cellClassNames: 'overflow-visible',
            wrapperClass: 'flex items-center justify-end mx-2',
            width: '10%',
            actions: [
                {
                    label: 'View Inventory',
                    fn: this.viewInventory,
                },
                {
                    label: 'Edit Inventory',
                    fn: this.editInventory,
                },
            ],
            sortable: false,
            filterable: false,
            resizable: false,
            searchable: false,
        },
    ];

    /**
     * The search task.
     *
     * @void
     */
    @task({ restartable: true }) *search({ target: { value } }) {
        // if no query don't search
        if (isBlank(value)) {
            this.query = null;
            return;
        }

        // timeout for typing
        yield timeout(250);

        // reset page for results
        if (this.page > 1) {
            this.page = 1;
        }

        // update the query param
        this.query = value;
    }

    /**
     * Toggles dialog to export `inventory`
     *
     * @void
     */
    @action exportProcuts() {
        this.crud.export('inventory');
    }

    /**
     * View a `inventory` details in overlay
     *
     * @param {InventoryModel} inventory
     * @param {Object} options
     * @void
     */
    @action viewInventory(inventory) {
        return this.transitionToRoute('inventory.index.details', inventory);
    }

    /**
     * Create a new `inventory` in modal
     *
     * @param {Object} options
     * @void
     */
    @action createInventory() {
        return this.transitionToRoute('inventory.index.new');
    }

    @action makeStockAdjustment() {
        return this.transitionToRoute('inventory.index.new-stock-adjustment');
    }

    /**
     * Edit a `inventory` details
     *
     * @param {InventoryModel} inventory
     * @param {Object} options
     * @void
     */
    @action async editInventory(inventory) {
        return this.transitionToRoute('inventory.index.edit', inventory);
    }

    /**
     * Delete a `inventory` via confirm prompt
     *
     * @param {InventoryModel} inventory
     * @param {Object} options
     * @void
     */
    @action deleteInventory(inventory, options = {}) {
        this.crud.delete(inventory, {
            onConfirm: () => {
                return this.hostRouter.refresh();
            },
            ...options,
        });
    }

    /**
     * Bulk deletes selected `inventory` via confirm prompt
     *
     * @param {Array} selected an array of selected models
     * @void
     */
    @action bulkDeleteInventorys() {
        const selected = this.table.selectedRows;

        this.crud.bulkDelete(selected, {
            modelNamePath: `public_id`,
            acceptButtonText: 'Delete Inventories',
            fetchOptions: {
                namespace: 'pallet/int/v1',
            },
            onSuccess: () => {
                return this.hostRouter.refresh();
            },
        });
    }
}
