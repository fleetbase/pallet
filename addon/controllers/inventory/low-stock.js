import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action, get } from '@ember/object';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';
import { getOwner } from '@ember/application';
import relatedRecordLabel from '../../utils/related-record-label';
import placeholderImage from '../../utils/placeholder-image';

export default class InventoryLowStockController extends Controller {
    @service intl;
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
    queryParams = ['page', 'limit', 'sort', 'product', 'warehouse', 'batch', 'status', 'view'];

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

    @tracked view = 'low_stock';

    /**
     * The param to sort the data on, the param with prepended `-` is descending
     *
     * @var {String}
     */
    /*
     * No client default: the server orders this view by depth below minimum, which
     * is the order someone actually works it in. Sending '-created_at' overrode that
     * and buried the deepest shortfall wherever it happened to have been created.
     */
    @tracked sort = null;

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
     * The filterable param `pallet-product`
     *
     * @var {String}
     */
    @tracked product;

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
    get placeholderImage() {
        return placeholderImage(getOwner(this));
    }

    @tracked columns = [
        {
            // ember-ui's shared identity cell, as on the inventory list. The
            // bespoke cell/product-info it replaces stacked a title, a meta line,
            // a description and three placeholder badges into every row; the SKU
            // it used to need a column of its own for is an identifier chip here.
            label: this.intl.t('columns.product'),
            valuePath: 'product.name',
            labelValue: (row) =>
                relatedRecordLabel(row, {
                    uuidPath: 'product_uuid',
                    relationPath: 'product',
                    missingLabel: this.intl.t('inventory.product-unavailable'),
                }),
            identifierPath: 'product.sku',
            mediaUrl: (row) => get(row, 'product.photo_url') || this.placeholderImage,
            imageSizeClass: 'h-5 w-5',
            showStatusDot: false,
            width: '240px',
            cellComponent: 'table/cell/resource-identity',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/string',
        },
        {
            label: this.intl.t('columns.product-sku'),
            valuePath: 'product.sku',
            cellComponent: 'click-to-copy',
            width: '120px',
            hidden: true,
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/string',
        },
        {
            // Same must-never as the index: `quantity` unlabelled is ambiguous.
            label: this.intl.t('inventory.fields.on-hand'),
            valuePath: 'quantity',
            cellComponent: 'cell/count',
            width: '100px',
        },
        {
            label: this.intl.t('inventory.fields.available'),
            valuePath: 'available_quantity',
            cellComponent: 'cell/count',
            width: '100px',
        },
        {
            label: this.intl.t('inventory.fields.min-quantity'),
            valuePath: 'min_quantity',
            cellComponent: 'cell/count',
            width: '100px',
        },
        {
            // The number the screen exists to surface. "quantity 3, min 5" makes a
            // reader subtract on every row to find the worst one; the server orders
            // by this same expression so the column and the sort agree.
            label: this.intl.t('inventory.fields.short-by'),
            valuePath: 'shortBy',
            cellComponent: 'cell/count',
            width: '100px',
        },
        {
            label: this.intl.t('columns.batch'),
            valuePath: 'batch.name',
            width: '120px',
            cellComponent: 'click-to-copy',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/string',
        },
        {
            label: this.intl.t('common.status'),
            valuePath: 'status',
            cellComponent: 'table/cell/status',
            width: '10%',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/multi-option',
        },
        {
            label: this.intl.t('columns.last-stocked'),
            valuePath: 'createdAt',
            sortParam: 'created_at',
            width: '10%',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/date',
        },
        {
            label: this.intl.t('common.updated-at'),
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
                    label: this.intl.t('actions.view-inventory'),
                    fn: this.viewInventory,
                },
                {
                    label: this.intl.t('actions.edit-inventory'),
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
        return this.hostRouter.transitionTo('console.pallet.inventory.index.details', inventory);
    }

    /**
     * Create a new `inventory` in modal
     *
     * @param {Object} options
     * @void
     */
    @action createInventory() {
        return this.hostRouter.transitionTo('console.pallet.inventory.index.new');
    }

    @action makeStockAdjustment() {
        return this.hostRouter.transitionTo('console.pallet.inventory.index.new-stock-adjustment');
    }

    /**
     * Edit a `inventory` details
     *
     * @param {InventoryModel} inventory
     * @param {Object} options
     * @void
     */
    @action async editInventory(inventory) {
        return this.hostRouter.transitionTo('console.pallet.inventory.index.edit', inventory);
    }
}
