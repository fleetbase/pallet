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
    queryParams = ['page', 'limit', 'sort', 'product', 'warehouse', 'batch', 'status'];

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
     * The filterable param `warehouse`
     *
     * @var {String}
     */
    @tracked warehouse;

    /**
     * The filterable param `batch`
     *
     * @var {String}
     */
    @tracked batch;

    /**
     * The filterable param `status`
     *
     * @var {String|Array}
     */
    @tracked status;

    /**
     * The filterable param `pallet-product`
     *
     * @var {String}
     */
    @tracked product;

    /**
     * All columns applicable for orders
     *
     * @var {Array}
     */
    @tracked columns = [
        {
            label: 'Product',
            valuePath: 'product.name',
            action: this.viewInventory,
            width: '170px',
            cellComponent: 'cell/product-info',
            modelPath: 'product',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/string',
        },
        {
            label: 'Product SKU',
            valuePath: 'product.sku',
            cellComponent: 'click-to-copy',
            width: '120px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/string',
        },
        {
            label: 'Quantity',
            valuePath: 'quantity',
            width: '120px',
        },
        {
            label: 'Batch',
            valuePath: 'batch.batch_number',
            width: '120px',
            cellComponent: 'click-to-copy',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/string',
        },
        {
            label: 'Status',
            valuePath: 'status',
            cellComponent: 'table/cell/status',
            width: '10%',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/multi-option',
        },
        {
            label: 'Last Stocked',
            valuePath: 'createdAt',
            sortParam: 'created_at',
            width: '10%',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/date',
        },
        {
            label: 'Expiry Date',
            valuePath: 'expiryDate',
            sortParam: 'expiry_date_at',
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
        return this.transitionToRoute('inventory.index.details', inventory.public_id);
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
}
