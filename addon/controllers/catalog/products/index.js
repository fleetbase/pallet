import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class ProductsIndexController extends Controller {
    @service productActions;
    @service tableContext;
    @service intl;

    /**
     * Queryable parameters for this controller's model
     *
     * @var {Array}
     */
    queryParams = [
        'page',
        'limit',
        'sort',
        'query',
        'internal_id',
        'public_id',
        'sku',
        'created_at',
        'updated_at',
        'name',
        'price',
        'sale_price',
        'declared_value',
        'length',
        'width',
        'height',
        'weight',
    ];

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
     * The filterable param `name`
     *
     * @var {String}
     */
    @tracked name;

    /**
     * The filterable param `product_id`
     *
     * @var {String}
     */
    @tracked product_id;

    /**
     * The filterable param `internal_id`
     *
     * @var {String}
     */
    @tracked internal_id;

    /**
     * The filterable param `price`
     *
     * @var {String}
     */
    @tracked price;

    /**
     * The filterable param `sale_price`
     *
     * @var {String}
     */
    @tracked sale_price;

    /**
     * The filterable param `declared_value`
     *
     * @var {String}
     */
    @tracked declared_value;

    /**
     * The filterable param `length`
     *
     * @var {String}
     */
    @tracked length;

    /**
     * The filterable param `width`
     *
     * @var {String}
     */
    @tracked width;

    /**
     * The filterable param `heigth`
     *
     * @var {String}
     */
    @tracked heigth;

    /**
     * The filterable param `weigth`
     *
     * @var {String}
     */
    @tracked weigth;

    /**
     * All columns applicable for orders
     *
     * @var {Array}
     */
    @tracked table;

    get actionButtons() {
        return [
            {
                icon: 'refresh',
                onClick: this.productActions.refresh,
                helpText: this.intl.t('common.refresh'),
            },
            {
                text: this.intl.t('common.new'),
                type: 'primary',
                icon: 'plus',
                onClick: this.productActions.transition.create,
            },
            {
                text: this.intl.t('common.export'),
                icon: 'long-arrow-up',
                iconClass: 'rotate-icon-45',
                wrapperClass: 'hidden md:flex',
                onClick: this.productActions.export,
            },
        ];
    }

    get bulkActions() {
        const selected = this.tableContext.getSelectedRows();

        return [
            {
                label: this.intl.t('common.delete-selected-count', { count: selected.length }),
                class: 'text-red-500',
                fn: this.productActions.bulkDelete,
            },
        ];
    }

    get emptyStateAction() {
        return {
            label: 'Create product',
            onClick: this.productActions.transition.create,
        };
    }

    get columns() {
        return [
            {
                label: 'Product',
                valuePath: 'name',
                width: '320px',
                cellComponent: 'cell/product-info',
                action: this.productActions.transition.view,
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'Stock',
                valuePath: 'storefrontInventoryStatus',
                width: '190px',
                cellComponent: 'cell/product-stock',
                resizable: true,
                sortable: false,
            },
            {
                label: 'Price & Value',
                valuePath: 'unit_price',
                width: '170px',
                cellComponent: 'cell/product-price',
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'Traceability',
                valuePath: 'is_lot_tracked',
                width: '170px',
                cellComponent: 'cell/product-traceability',
                resizable: true,
                sortable: false,
            },
            {
                label: 'Status',
                valuePath: 'status',
                width: '120px',
                cellComponent: 'table/cell/status',
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'Storefront',
                valuePath: 'storefrontLinkStatus',
                width: '160px',
                cellComponent: 'cell/product-storefront',
                resizable: true,
                sortable: false,
            },
            {
                label: 'ID',
                valuePath: 'public_id',
                width: '130px',
                cellComponent: 'click-to-copy',
                hidden: true,
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'SKU',
                valuePath: 'sku',
                cellComponent: 'click-to-copy',
                width: '130px',
                hidden: true,
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'Internal ID',
                valuePath: 'internal_id',
                cellComponent: 'click-to-copy',
                width: '130px',
                hidden: true,
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'Storefront Product',
                valuePath: 'storefront_product_uuid',
                cellComponent: 'click-to-copy',
                width: '180px',
                hidden: true,
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'Date Added',
                valuePath: 'createdAt',
                sortParam: 'created_at',
                width: '10%',
                resizable: true,
                sortable: true,
                hidden: true,
                filterable: true,
                filterComponent: 'filter/date',
            },
            {
                label: 'Last Updated',
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
                ddMenuLabel: this.intl.t('common.resource-actions', { resource: 'Product' }),
                cellClassNames: 'overflow-visible',
                wrapperClass: 'flex items-center justify-end mx-2',
                width: '10%',
                actions: [
                    {
                        label: this.intl.t('common.view-resource', { resource: 'Product' }),
                        fn: this.productActions.transition.view,
                    },
                    {
                        label: this.intl.t('common.edit-resource', { resource: 'Product' }),
                        fn: this.productActions.transition.edit,
                    },
                    {
                        separator: true,
                    },
                    {
                        label: this.intl.t('common.delete-resource', { resource: 'Product' }),
                        fn: this.productActions.delete,
                    },
                ],
                sortable: false,
                filterable: false,
                resizable: false,
                searchable: false,
            },
        ];
    }

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
}
