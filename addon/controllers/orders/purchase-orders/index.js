import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { isBlank } from '@ember/utils';
import { timeout } from 'ember-concurrency';
import { task } from 'ember-concurrency-decorators';

export default class PurchaseOrdersIndexController extends Controller {
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
    queryParams = ['page', 'limit', 'sort', 'query', 'public_id', 'created_by', 'updated_by', 'status', 'delivery_date_at'];

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
        { value: 'received', label: this.intl.t('status.received') },
        { value: 'cancelled', label: this.intl.t('status.cancelled') },
    ];

    /**
     * All columns applicable for purchase orders
     *
     * @var {Array}
     */
    @tracked columns = [
        {
            // the list identified an order by its record id because there was no
            // order number to show; there is one now, and the id keeps its own
            // column behind the picker for when someone needs it
            label: this.intl.t('columns.po-number'),
            valuePath: 'order_number',
            width: '140px',
            cellComponent: 'table/cell/anchor',
            action: this.viewPurchaseOrder,
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
            label: this.intl.t('columns.supplier'),
            valuePath: 'supplier.name',
            width: '160px',
            resizable: true,
            sortable: false,
            filterable: false,
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
            ddMenuLabel: 'Purchase Order Actions',
            cellClassNames: 'overflow-visible',
            wrapperClass: 'flex items-center justify-end mx-2',
            width: '10%',
            actions: [
                {
                    label: this.intl.t('actions.view-details'),
                    fn: this.viewPurchaseOrder,
                },
                {
                    label: this.intl.t('actions.receive-goods'),
                    fn: this.receivePurchaseOrder,
                    isVisible: (purchaseOrder) => ['pending', 'partial'].includes(purchaseOrder.status),
                },
                {
                    separator: true,
                },
                {
                    label: this.intl.t('actions.edit-purchase-order'),
                    fn: this.editPurchaseOrder,
                },
                {
                    separator: true,
                },
                {
                    label: this.intl.t('actions.delete-purchase-order'),
                    fn: this.deletePurchaseOrder,
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
                onClick: this.createPurchaseOrder,
            },
            {
                text: this.intl.t('common.export'),
                icon: 'long-arrow-up',
                iconClass: 'rotate-icon-45',
                wrapperClass: 'hidden md:flex',
                onClick: this.exportPurchaseOrders,
            },
        ];
    }

    /**
     * Export purchase orders
     *
     * @void
     */
    @action exportPurchaseOrders() {
        this.crud.export('purchase-order');
    }

    /**
     * View the selected Purchase Order
     *
     * @param {PurchaseOrderModel} purchaseOrder
     * @void
     */
    @action viewPurchaseOrder(purchaseOrder) {
        this.hostRouter.transitionTo('console.pallet.orders.purchase-orders.index.details', purchaseOrder);
    }

    /**
     * Create a new Purchase Order
     *
     * @void
     */
    @action createPurchaseOrder() {
        this.hostRouter.transitionTo('console.pallet.orders.purchase-orders.index.new');
    }

    /**
     * Edit a Purchase Order
     *
     * @param {PurchaseOrderModel} purchaseOrder
     * @void
     */
    @action editPurchaseOrder(purchaseOrder) {
        this.hostRouter.transitionTo('console.pallet.orders.purchase-orders.index.edit', purchaseOrder);
    }

    /**
     * Open the Receive Goods panel for a Purchase Order.
     * Loads the PO with its items before opening the panel.
     *
     * @param {PurchaseOrderModel} purchaseOrder
     * @void
     */
    @action async receivePurchaseOrder(purchaseOrder) {
        this.loader.showOnInitialTransition = false;

        // Ensure items are loaded
        if (!purchaseOrder.items || purchaseOrder.items.length === 0) {
            try {
                await purchaseOrder.reload();
            } catch (e) {
                // proceed with whatever is loaded
            }
        }

        this.contextPanel.focus(purchaseOrder, 'receiving', {
            args: {
                onReceived: () => {
                    this.hostRouter.refresh();
                },
            },
        });
    }

    /**
     * Prompt to delete a Purchase Order
     *
     * @param {PurchaseOrderModel} purchaseOrder
     * @param {Object} options
     * @void
     */
    @action deletePurchaseOrder(purchaseOrder, options = {}) {
        this.crud.delete(purchaseOrder, {
            onConfirm: () => {
                this.hostRouter.refresh();
            },
            ...options,
        });
    }

    /**
     * Bulk deletes selected Purchase Orders via confirm prompt
     *
     * @param {Array} selected an array of selected models
     * @void
     */
    @action bulkDeletePurchaseOrder() {
        const selected = this.table.selectedRows;

        this.crud.bulkDelete(selected, {
            modelNamePath: 'public_id',
            acceptButtonText: 'Delete Purchase Orders',
            onSuccess: () => {
                return this.hostRouter.refresh();
            },
        });
    }
}
