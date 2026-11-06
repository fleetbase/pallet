import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { isBlank } from '@ember/utils';
import { timeout } from 'ember-concurrency';
import { task } from 'ember-concurrency-decorators';

export default class SalesOrdersIndexController extends Controller {
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
     * Inject the `crud` service
     *
     * @var {Service}
     */
    @service crud;

    /**
     * Inject the `store` service
     *
     * @var {Service}
     */
    @service store;

    /**
     * Inject the `hostRouter` service
     *
     * @var {Service}
     */
    @service hostRouter;

    /**
     * Inject the `contextPanel` service
     *
     * @var {Service}
     */
    @service contextPanel;

    /**
     * Inject the `filters` service
     *
     * @var {Service}
     */
    @service filters;

    /**
     * Inject the `loader` service
     *
     * @var {Service}
     */
    @service loader;

    /**
     * Queryable parameters for this controller's model
     *
     * @var {Array}
     */
    queryParams = ['page', 'limit', 'sort', 'query', 'public_id', 'created_by', 'updated_by', 'status', 'delivered_at'];

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
     * The filterable param `public_id`
     *
     * @var {String}
     */
    @tracked public_id;

    /**
     * The filterable param `status`
     *
     * @var {Array}
     */
    @tracked status;

    /**
     * The table instance
     *
     * @var {Object}
     */
    @tracked table;

    /**
     * Status options for the filter
     *
     * @var {Array}
     */
    statusOptions = [
        { value: 'pending', label: this.intl.t('status.pending') },
        { value: 'partial', label: this.intl.t('status.partial') },
        { value: 'fulfilled', label: this.intl.t('status.fulfilled') },
        { value: 'cancelled', label: this.intl.t('status.cancelled') },
    ];

    /**
     * All columns applicable for sales orders
     *
     * @var {Array}
     */
    @tracked columns = [
        {
            // the list identified an order by its record id because there was no
            // order number to show; there is one now, and the id keeps its own
            // column behind the picker for when someone needs it
            label: this.intl.t('columns.so-number'),
            valuePath: 'order_number',
            width: '140px',
            cellComponent: 'table/cell/anchor',
            action: this.viewSalesOrder,
            resizable: true,
            sortable: true,
            filterable: true,
            hidden: false,
            filterComponent: 'filter/string',
        },
        {
            label: this.intl.t('common.id'),
            valuePath: 'public_id',
            width: '160px',
            cellComponent: 'click-to-copy',
            resizable: true,
            sortable: true,
            hidden: true,
        },
        {
            label: this.intl.t('columns.customer-ref'),
            valuePath: 'customer_reference_code',
            width: '140px',
            resizable: true,
            sortable: false,
            filterable: true,
            filterComponent: 'filter/string',
        },
        {
            label: this.intl.t('columns.reference'),
            valuePath: 'reference_code',
            width: '130px',
            resizable: true,
            sortable: false,
            filterable: true,
            filterComponent: 'filter/string',
        },
        {
            label: this.intl.t('columns.items'),
            valuePath: 'item_count',
            cellComponent: 'cell/count',
            width: '70px',
            resizable: false,
            sortable: false,
            filterable: false,
        },
        {
            label: this.intl.t('common.status'),
            valuePath: 'status',
            cellComponent: 'table/cell/status',
            width: '110px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/multi-option',
            filterOptions: this.statusOptions,
        },
        {
            label: this.intl.t('columns.expected-delivery'),
            valuePath: 'expected_delivery_at',
            width: '140px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/date',
        },
        {
            label: this.intl.t('common.created-at'),
            valuePath: 'createdAt',
            sortParam: 'created_at',
            width: '120px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/date',
        },
        {
            label: this.intl.t('common.updated-at'),
            valuePath: 'updatedAt',
            sortParam: 'updated_at',
            width: '120px',
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
            ddMenuLabel: 'Sales Order Actions',
            cellClassNames: 'overflow-visible',
            wrapperClass: 'flex items-center justify-end mx-2',
            width: '10%',
            actions: [
                {
                    label: this.intl.t('actions.view-details'),
                    fn: this.viewSalesOrder,
                },
                {
                    label: this.intl.t('actions.fulfill-order'),
                    fn: this.fulfillSalesOrder,
                    isVisible: (salesOrder) => ['pending', 'partial'].includes(salesOrder.status),
                },
                {
                    separator: true,
                },
                {
                    label: this.intl.t('actions.edit-sales-order'),
                    fn: this.editSalesOrder,
                },
                {
                    separator: true,
                },
                {
                    label: this.intl.t('actions.delete-sales-order'),
                    fn: this.deleteSalesOrder,
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
     * Toolbar buttons for the table.
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
                onClick: this.createSalesOrder,
            },
            {
                text: this.intl.t('common.export'),
                icon: 'long-arrow-up',
                iconClass: 'rotate-icon-45',
                wrapperClass: 'hidden md:flex',
                onClick: this.exportSalesOrders,
            },
        ];
    }

    /**
     * Export sales orders
     *
     * @void
     */
    @action exportSalesOrders() {
        this.crud.export('sales-order');
    }

    /**
     * View the selected Sales Order
     *
     * @param {SalesOrderModel} salesOrder
     * @void
     */
    @action viewSalesOrder(salesOrder) {
        this.hostRouter.transitionTo('console.pallet.orders.sales-orders.details', salesOrder);
    }

    /**
     * Create a new Sales Order
     *
     * @void
     */
    @action createSalesOrder() {
        this.hostRouter.transitionTo('console.pallet.orders.sales-orders.index.new');
    }

    /**
     * Edit a Sales Order
     *
     * @param {SalesOrderModel} salesOrder
     * @void
     */
    @action editSalesOrder(salesOrder) {
        this.hostRouter.transitionTo('console.pallet.orders.sales-orders.index.edit', salesOrder);
    }

    /**
     * Open the Fulfill Order panel for a Sales Order.
     * Loads the SO with its items before opening the panel.
     *
     * @param {SalesOrderModel} salesOrder
     * @void
     */
    @action async fulfillSalesOrder(salesOrder) {
        this.loader.showOnInitialTransition = false;

        // Ensure items are loaded
        if (!salesOrder.items || salesOrder.items.length === 0) {
            try {
                await salesOrder.reload();
            } catch (e) {
                // proceed with whatever is loaded
            }
        }

        this.contextPanel.focus(salesOrder, 'fulfilling', {
            args: {
                onFulfilled: () => {
                    this.hostRouter.refresh();
                },
            },
        });
    }

    /**
     * Prompt to delete a Sales Order
     *
     * @param {SalesOrderModel} salesOrder
     * @param {Object} options
     * @void
     */
    @action deleteSalesOrder(salesOrder, options = {}) {
        this.crud.delete(salesOrder, {
            onConfirm: () => {
                this.hostRouter.refresh();
            },
            ...options,
        });
    }

    /**
     * Bulk deletes selected Sales Orders via confirm prompt
     *
     * @param {Array} selected an array of selected models
     * @void
     */
    @action bulkDeleteSalesOrders() {
        const selected = this.table.selectedRows;

        this.crud.bulkDelete(selected, {
            modelNamePath: 'public_id',
            acceptButtonText: 'Delete Sales Orders',
            onSuccess: () => {
                return this.hostRouter.refresh();
            },
        });
    }
}
