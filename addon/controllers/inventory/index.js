import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action, get } from '@ember/object';
import { getOwner } from '@ember/application';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class InventoryIndexController extends Controller {
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
     * Products routinely have no photo, and an <img> with no src draws the
     * browser's broken-image glyph in every row.
     *
     * `getOwner` inside a mounted engine resolves the engine's own config, which
     * is why Pallet declares defaultValues of its own — the console's copy is not
     * visible from in here.
     */
    get placeholderImage() {
        const config = getOwner(this)?.resolveRegistration?.('config:environment');

        return get(config ?? {}, 'defaultValues.placeholderImage');
    }

    /**
     * An inventory row keeps its product_uuid when the product is deleted, and the
     * relation then resolves to nothing. The relationship is async, so the proxy
     * stays truthy even with null content — test the resolved record instead.
     * Without this the row would render a nameless product rather than saying the
     * product is gone.
     */
    productLabel(row) {
        const product = get(row ?? {}, 'product');
        const resolved = product ? (get(product, 'content') ?? product) : null;

        if (get(row ?? {}, 'product_uuid') && !get(resolved ?? {}, 'uuid')) {
            return this.intl.t('inventory.product-unavailable');
        }

        return get(resolved ?? {}, 'name');
    }

    /**
     * All columns applicable for orders
     *
     * @var {Array}
     */
    @tracked columns = [
        {
            // ember-ui's shared identity cell: one truncated line plus up to three
            // meta chips, on a 20px avatar. The bespoke cell/product-info it replaces
            // stacked a title, a meta line, a description and three badges, which made
            // every row ~109px tall and fitted five on a screen.
            label: this.intl.t('columns.product'),
            valuePath: 'product.name',
            labelValue: (row) => this.productLabel(row),
            identifierPath: 'product.sku',
            // resolved per row rather than by path: the placeholder comes from the
            // host config, which is not readable while the class fields initialise
            mediaUrl: (row) => get(row, 'product.photo_url') || this.placeholderImage,
            metaPaths: [{ path: 'batch.batch_number', style: 'badge' }],
            imageSizeClass: 'h-5 w-5',
            showStatusDot: false,
            cellComponent: 'table/cell/resource-identity',
            action: this.viewInventory,
            width: '240px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterParam: 'product',
            filterComponent: 'filter/string',
        },
        {
            // the list showed two identical "Steel Shelving Bracket" rows holding 46
            // and 18 units with nothing to tell them apart — stock is meaningless
            // without the place it is held
            label: this.intl.t('operations.common.warehouse'),
            valuePath: 'warehouse.name',
            cellComponent: 'table/cell/base',
            width: '180px',
            resizable: true,
            sortable: false,
            filterable: false,
        },
        {
            label: this.intl.t('columns.quantity'),
            valuePath: 'quantity',
            cellComponent: 'cell/count',
            width: '90px',
            resizable: true,
            sortable: true,
        },
        {
            label: this.intl.t('inventory.fields.reserved'),
            valuePath: 'reserved_quantity',
            cellComponent: 'cell/count',
            width: '90px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('inventory.fields.available'),
            valuePath: 'available_quantity',
            cellComponent: 'cell/count',
            width: '90px',
            resizable: true,
            sortable: false,
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
            label: this.intl.t('columns.expiry-date'),
            valuePath: 'expiryDate',
            sortParam: 'expiry_date_at',
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
     * Toolbar buttons for the inventory table.
     *
     * @var {Array}
     */
    get actionButtons() {
        return [
            {
                icon: 'refresh',
                onClick: () => this.hostRouter.refresh(),
                helpText: this.intl.t('common.refresh'),
            },
            {
                text: this.intl.t('common.new'),
                type: 'primary',
                icon: 'plus',
                onClick: this.createInventory,
            },
            {
                text: this.intl.t('inventory.screens.new-adjustment'),
                icon: 'sliders',
                wrapperClass: 'hidden md:flex',
                onClick: this.makeStockAdjustment,
            },
            {
                text: this.intl.t('common.export'),
                icon: 'long-arrow-up',
                iconClass: 'rotate-icon-45',
                wrapperClass: 'hidden md:flex',
                onClick: this.exportProcuts,
            },
        ];
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
        return this.hostRouter.transitionTo('console.pallet.inventory.index.details', inventory.public_id);
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
