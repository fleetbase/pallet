import Controller, { inject as controller } from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { isBlank } from '@ember/utils';
import { timeout } from 'ember-concurrency';
import { task } from 'ember-concurrency-decorators';
import getSupplierStatusOptions from '../../utils/get-supplier-status-options';
export default class SuppliersIndexController extends Controller {
    /**
     * Inject the `warehouses.index` controller
     *
     * @var {Controller}
     */
    @controller('warehouses.index') warehouses;

    @service supplierActions;
    @service warehouseActions;
    @service tableContext;
    @service intl;
    @service store;

    /**
     * Queryable parameters for this controller's model
     *
     * @var {Array}
     */
    queryParams = ['page', 'limit', 'sort', 'query', 'public_id', 'internal_id', 'created_by', 'updated_by', 'status', 'name', 'email', 'phone', 'type', 'country', 'address', 'website_url'];

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
     * The filterable param `internal_id`
     *
     * @var {String}
     */
    @tracked internal_id;

    /**
     * The filterable param `status`
     *
     * @var {Array}
     */
    @tracked status;

    /**
     * The filterable param `type`
     *
     * @var {Array|String}
     */
    @tracked type;

    /**
     * The filterable param `name`
     *
     * @var {String}
     */
    @tracked name;

    /**
     * The filterable param `website_url`
     *
     * @var {String}
     */
    @tracked website_url;

    /**
     * The filterable param `phone`
     *
     * @var {String}
     */
    @tracked phone;

    /**
     * The filterable param `email`
     *
     * @var {String}
     */
    @tracked email;

    /**
     * The filterable param `country`
     *
     * @var {String}
     */
    @tracked country;

    /**
     * Rows for the table
     *
     * @var {Array}
     */
    @tracked table;

    get actionButtons() {
        return [
            {
                icon: 'refresh',
                onClick: this.supplierActions.refresh,
                helpText: this.intl.t('common.refresh'),
            },
            {
                text: this.intl.t('common.new'),
                type: 'primary',
                icon: 'plus',
                onClick: this.supplierActions.transition.create,
            },
            {
                text: this.intl.t('common.export'),
                icon: 'long-arrow-up',
                iconClass: 'rotate-icon-45',
                wrapperClass: 'hidden md:flex',
                onClick: this.supplierActions.export,
            },
        ];
    }

    get bulkActions() {
        const selected = this.tableContext.getSelectedRows();

        return [
            {
                label: this.intl.t('common.delete-selected-count', { count: selected.length }),
                class: 'text-red-500',
                fn: this.supplierActions.bulkDelete,
            },
        ];
    }

    get columns() {
        return [
            {
                label: 'Name',
                valuePath: 'name',
                width: '180px',
                cellComponent: 'table/cell/media-name',
                mediaPath: 'logo_url',
                action: this.supplierActions.transition.view,
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'ID',
                valuePath: 'public_id',
                cellComponent: 'click-to-copy',
                width: '110px',
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'Internal ID',
                valuePath: 'internal_id',
                cellComponent: 'click-to-copy',
                width: '100px',
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'Email',
                valuePath: 'email',
                cellComponent: 'click-to-copy',
                width: '80px',
                resizable: true,
                sortable: true,
                hidden: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'Website URL',
                valuePath: 'website_url',
                cellComponent: 'click-to-copy',
                width: '80px',
                resizable: true,
                sortable: true,
                hidden: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'Phone',
                valuePath: 'phone',
                cellComponent: 'click-to-copy',
                width: '80px',
                resizable: true,
                sortable: true,
                hidden: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: 'Address',
                valuePath: 'address',
                cellComponent: 'table/cell/anchor',
                action: this.viewSupplierWarehouse,
                width: '150px',
                resizable: true,
                sortable: true,
                filterable: true,
                filterParam: 'address',
                filterComponent: 'filter/string',
            },
            {
                label: 'Type',
                valuePath: 'type',
                width: '150px',
                resizable: true,
                sortable: true,
                filterable: true,
                filterParam: 'type',
                filterComponent: 'filter/string',
            },
            {
                label: 'Country',
                valuePath: 'country',
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
                label: 'Created At',
                valuePath: 'createdAt',
                sortParam: 'created_at',
                width: '150px',
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/date',
            },
            {
                label: 'Updated At',
                valuePath: 'updatedAt',
                sortParam: 'updated_at',
                width: '130px',
                resizable: true,
                sortable: true,
                hidden: true,
                filterable: true,
                filterComponent: 'filter/date',
            },
            {
                label: 'Status',
                valuePath: 'status',
                cellComponent: 'table/cell/status',
                width: '130px',
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/multi-option',
                filterOptions: getSupplierStatusOptions(),
            },
            {
                label: '',
                cellComponent: 'table/cell/dropdown',
                ddButtonText: false,
                ddButtonIcon: 'ellipsis-h',
                ddButtonIconPrefix: 'fas',
                ddMenuLabel: this.intl.t('common.resource-actions', { resource: 'Supplier' }),
                cellClassNames: 'overflow-visible',
                wrapperClass: 'flex items-center justify-end mx-2',
                width: '7%',
                actions: [
                    {
                        label: this.intl.t('common.view-resource', { resource: 'Supplier' }),
                        fn: this.supplierActions.transition.view,
                    },
                    {
                        label: this.intl.t('common.edit-resource', { resource: 'Supplier' }),
                        fn: this.supplierActions.transition.edit,
                    },
                    {
                        separator: true,
                    },
                    {
                        label: this.intl.t('common.delete-resource', { resource: 'Supplier' }),
                        fn: this.supplierActions.delete,
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

    @action async viewSupplierWarehouse(supplier) {
        if (!supplier?.warehouse_uuid) {
            return;
        }

        const warehouse = await this.store.findRecord('warehouse', supplier.warehouse_uuid);
        if (warehouse) {
            return this.warehouseActions.transition.view(warehouse);
        }
    }
}
