import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class WarehousesIndexController extends Controller {
    @service warehouseActions;
    @service tableContext;
    @service intl;

    /**
     * Queryable parameters for this controller's model
     *
     * @var {Array}
     */
    queryParams = ['name', 'page', 'limit', 'sort', 'query', 'public_id', 'country', 'phone', 'created_at', 'updated_at', 'city', 'neighborhood', 'state', 'description'];

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
     * The filterable param `public_id`
     *
     * @var {String}
     */
    @tracked postal_code;

    /**
     * The filterable param `phone`
     *
     * @var {String}
     */
    @tracked phone;

    /**
     * The filterable param `city`
     *
     * @var {String}
     */
    @tracked city;

    /**
     * The filterable param `name`
     *
     * @var {String}
     */
    @tracked name;

    /**
     * The filterable param `country`
     *
     * @var {String}
     */
    @tracked country;

    /**
     * The filterable param `country`
     *
     * @var {String}
     */
    @tracked neighborhood;

    @tracked table;

    get actionButtons() {
        return [
            {
                icon: 'refresh',
                onClick: this.warehouseActions.refresh,
                helpText: this.intl.t('common.refresh'),
            },
            {
                text: this.intl.t('common.new'),
                type: 'primary',
                icon: 'plus',
                onClick: this.warehouseActions.transition.create,
            },
            {
                text: this.intl.t('common.export'),
                icon: 'long-arrow-up',
                iconClass: 'rotate-icon-45',
                wrapperClass: 'hidden md:flex',
                onClick: this.warehouseActions.export,
            },
        ];
    }

    get bulkActions() {
        const selected = this.tableContext.getSelectedRows();

        return [
            {
                label: this.intl.t('common.delete-selected-count', { count: selected.length }),
                class: 'text-red-500',
                fn: this.warehouseActions.bulkDelete,
            },
        ];
    }

    get columns() {
        return [
            {
                label: 'Name',
                valuePath: 'name',
                width: '200px',
                cellComponent: 'table/cell/anchor',
                action: this.warehouseActions.transition.view,
                resizable: true,
                sortable: true,
                filterable: true,
                filterParam: 'name',
                filterComponent: 'filter/string',
            },
            {
                label: 'Description',
                valuePath: 'meta.description',
                width: '200px',
                cellComponent: 'table/cell/anchor',
                resizable: true,
                sortable: true,
                filterable: true,
                filterParam: 'description',
                filterComponent: 'filter/string',
            },
            {
                label: 'Address',
                valuePath: 'address',
                cellComponent: 'table/cell/anchor',
                action: this.warehouseActions.transition.view,
                width: '320px',
                resizable: true,
                sortable: true,
                filterable: true,
                filterParam: 'address',
                filterComponent: 'filter/string',
            },
            {
                label: 'Stock Items',
                valuePath: 'stock_items',
                width: '120px',
                cellComponent: 'table/cell/anchor',
                // the anchor cell falls back on any falsy value, so a warehouse
                // holding no stock read as an em dash rather than as zero
                anchorText: '0',
                resizable: true,
                sortable: true,
                filterable: false,
            },
            {
                label: 'ID',
                valuePath: 'public_id',
                width: '120px',
                cellComponent: 'click-to-copy',
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'Country',
                valuePath: 'country_name',
                cellComponent: 'table/cell/base',
                cellClassNames: 'uppercase',
                width: '120px',
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/country',
                filterParam: 'country',
            },
            {
                label: 'Structural',
                valuePath: 'meta.structural',
                width: '120px',
                cellComponent: 'table/cell/base',
                resizable: true,
                sortable: true,
                filterable: true,
                filterParam: 'structural',
                filterComponent: 'filter/string',
            },
            {
                label: 'External',
                valuePath: 'meta.external',
                width: '120px',
                cellComponent: 'table/cell/base',
                resizable: true,
                sortable: true,
                filterable: true,
                filterParam: 'external',
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
                ddMenuLabel: this.intl.t('common.resource-actions', { resource: 'Warehouse' }),
                cellClassNames: 'overflow-visible',
                wrapperClass: 'flex items-center justify-end mx-2',
                width: '10%',
                actions: [
                    {
                        label: this.intl.t('common.view-resource', { resource: 'Warehouse' }),
                        fn: this.warehouseActions.transition.view,
                    },
                    {
                        label: this.intl.t('common.edit-resource', { resource: 'Warehouse' }),
                        fn: this.warehouseActions.transition.edit,
                    },
                    {
                        separator: true,
                    },
                    {
                        label: 'View Warehouse on Map',
                        fn: this.warehouseActions.locate,
                    },
                    {
                        separator: true,
                    },
                    {
                        label: this.intl.t('common.delete-resource', { resource: 'Warehouse' }),
                        fn: this.warehouseActions.delete,
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
