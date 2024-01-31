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
     * Inject the `fetch` service
     *
     * @var {Service}
     */
    @service fetch;

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
    @tracked rows = [];

    /**
     * All columns for the table
     *
     * @var {Array}
     */
    @tracked columns = [
        {
            label: 'Name',
            valuePath: 'name',
            width: '180px',
            cellComponent: 'table/cell/media-name',
            mediaPath: 'logo_url',
            action: this.viewSupplier,
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
            action: this.viewSupplierPlace,
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
            cellComponent: 'table/cell/anchor',
            action: this.viewSupplierPlace,
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
            ddMenuLabel: 'Supplier Actions',
            cellClassNames: 'overflow-visible',
            wrapperClass: 'flex items-center justify-end mx-2',
            width: '7%',
            actions: [
                {
                    label: 'View Supplier Details',
                    fn: this.viewSupplier,
                },
                {
                    label: 'Edit Supplier',
                    fn: this.editSupplier,
                },
                {
                    separator: true,
                },
                {
                    label: 'Delete Supplier',
                    fn: this.deleteSupplier,
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
     * Toggles dialog to export `supplier`
     *
     * @void
     */
    @action exportSuppliers() {
        this.crud.export('supplier');
    }

    /**
     * View a `supplier` details in modal
     *
     * @param {SupplierModel} supplier
     * @void
     */
    @action viewSupplier(supplier) {
        return this.transitionToRoute('suppliers.index.details', supplier);
    }

    /**
     * Create a new `supplier` in modal
     *
     * @void
     */
    @action async createSupplier() {
        return this.transitionToRoute('suppliers.index.new');
    }

    /**
     * Edit a `supplier` details
     *
     * @param {SupplierModel} supplier
     * @void
     */
    @action editSupplier(supplier) {
        return this.transitionToRoute('suppliers.index.edit', supplier);
    }

    /**
     * Delete a `supplier` via confirm prompt
     *
     * @param {SupplierModel} supplier
     * @param {Object} options
     * @void
     */
    @action deleteSupplier(supplier, options = {}) {
        this.crud.delete(supplier, {
            acceptButtonIcon: 'trash',
            onSuccess: () => {
                return this.hostRouter.refresh();
            },
            ...options,
        });
    }

    /**
     * Bulk deletes selected `supplier` via confirm prompt
     *
     * @param {Array} selected an array of selected models
     * @void
     */
    @action bulkDeleteSuppliers() {
        const selected = this.table.selectedRows;

        this.crud.bulkDelete(selected, {
            modelNamePath: `name`,
            acceptButtonText: 'Delete Suppliers',
            onSuccess: () => {
                return this.hostRouter.refresh();
            },
        });
    }

    /**
     * View information about the suppliers warehouse
     *
     * @param {SupplierModel} supplier
     * @void
     */
    @action async viewSupplierPlace(supplier) {
        const warehouse = await this.store.findRecord('warehouse', supplier.warehouse_uuid);

        if (warehouse) {
            this.contextPanel.focus(warehouse);
        }
    }

    /**
     * Edit a supplier's current warehouse
     *
     * @param {SupplierModel} supplier
     * @void
     */
    @action async editSupplierPlace(supplier) {
        const warehouse = await this.store.findRecord('warehouse', supplier.warehouse_uuid);

        if (warehouse) {
            this.contextPanel.focus(warehouse, 'editing');
        }
    }

    /**
     * Create a new warehouse for a supplier.
     *
     * @param {SupplierModel} supplier
     * @void
     */
    @action async createSupplierPlace(supplier) {
        const warehouse = this.store.createRecord('warehouse');

        this.contextPanel.focus(warehouse, 'editing', {
            onAfterSave: (warehouse) => {
                supplier.set('warehouse_uuid', warehouse.id);
                supplier.save();
            },
        });
    }
}
