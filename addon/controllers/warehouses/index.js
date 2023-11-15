import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class WarehousesIndexController extends Controller {
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
    queryParams = ['name', 'page', 'limit', 'sort', 'query', 'public_id', 'country', 'phone', 'created_at', 'updated_at', 'city', 'neighborhood', 'state'];

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

    /**
     * All columns applicable for orders
     *
     * @var {Array}
     */
    @tracked columns = [
        {
            label: 'Name',
            valuePath: 'name',
            width: '200px',
            cellComponent: 'table/cell/anchor',
            action: this.viewWarehouse,
            resizable: true,
            sortable: true,
            filterable: true,
            filterParam: 'name',
            filterComponent: 'filter/string',
        },
        {
            label: 'Address',
            valuePath: 'address',
            cellComponent: 'table/cell/anchor',
            action: this.viewWarehouse,
            width: '320px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterParam: 'address',
            filterComponent: 'filter/string',
        },
        {
            label: 'State',
            valuePath: 'state',
            cellComponent: 'table/cell/anchor',
            action: this.viewWarehouse,
            width: '100px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterParam: 'state',
            filterComponent: 'filter/string',
        },
        {
            label: 'City',
            valuePath: 'city',
            cellComponent: 'table/cell/anchor',
            action: this.viewWarehouse,
            width: '100px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterParam: 'city',
            filterComponent: 'filter/string',
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
            label: 'Phone',
            valuePath: 'phone',
            cellComponent: 'table/cell/base',
            width: '120px',
            resizable: true,
            sortable: true,
            hidden: true,
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
            label: 'Neighborhood',
            valuePath: 'neighborhood',
            cellComponent: 'table/cell/anchor',
            action: this.viewWarehouse,
            width: '100px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterParam: 'neighborhood',
            filterComponent: 'filter/string',
        },
        {
            label: 'Postal Code',
            valuePath: 'postal_code',
            cellComponent: 'table/cell/anchor',
            action: this.viewWarehouse,
            width: '100px',
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
            ddMenuLabel: 'Warehouse Actions',
            cellClassNames: 'overflow-visible',
            wrapperClass: 'flex items-center justify-end mx-2',
            width: '10%',
            actions: [
                {
                    label: 'View Warehouse Details',
                    fn: this.viewWarehouse,
                },
                {
                    label: 'Edit Warehouse',
                    fn: this.viewWarehouse,
                },
                {
                    separator: true,
                },
                {
                    label: 'View Warehouse on Map',
                    fn: this.viewWarehouse,
                },
                {
                    separator: true,
                },
                {
                    label: 'Delete Warehouse',
                    fn: this.deleteWarehouse,
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
     * Toggles dialog to export `warehouse`
     *
     * @void
     */
    @action exportProcuts() {
        this.crud.export('warehouse');
    }

    /**
     * View a `warehouse` details in overlay
     *
     * @param {WarehouseModel} warehouse
     * @param {Object} options
     * @void
     */
    @action viewWarehouse(warehouse) {
        this.transitionToRoute('warehouses.index.details', warehouse);
    }

    /**
     * Create a new `warehouse` in modal
     *
     * @param {Object} options
     * @void
     */
    @action createWarehouse() {
        this.transitionToRoute('warehouses.index.new');
    }

    /**
     * Edit a `warehouse` details
     *
     * @param {WarehouseModel} warehouse
     * @param {Object} options
     * @void
     */
    @action async editWarehouse(warehouse) {
        this.transitionToRoute('warehouses.index.edit', warehouse);
    }

    /**
     * Delete a `warehouse` via confirm prompt
     *
     * @param {WarehouseModel} warehouse
     * @param {Object} options
     * @void
     */
    @action deleteWarehouse(warehouse, options = {}) {
        this.crud.delete(warehouse, {
            onConfirm: () => {
                return this.hostRouter.refresh();
            },
            ...options,
        });
    }

    /**
     * Bulk deletes selected `warehouse` via confirm prompt
     *
     * @param {Array} selected an array of selected models
     * @void
     */
    @action bulkDeleteWarehouses() {
        const selected = this.table.selectedRows;

        this.crud.bulkDelete(selected, {
            modelNamePath: `address`,
            acceptButtonText: 'Delete Warehouse',
            onSuccess: () => {
                return this.hostRouter.refresh();
            },
        });
    }

    /**
     * Prompt user to assign a `vendor` to a `place`
     *
     * @param {PlaceModel} place
     * @param {Object} options
     * @void
     */

    /**
     * View a place location on map
     *
     * @param {WarehouseModel} place
     * @param {Object} options
     * @void
     */
    @action viewOnMap(warehouse, options = {}) {
        const { latitude, longitude } = warehouse;

        this.modalsManager.show('modals/point-map', {
            title: `Location of ${warehouse.name}`,
            acceptButtonText: 'Done',
            hideDeclineButton: true,
            latitude,
            longitude,
            location: [latitude, longitude],
            ...options,
        });
    }
}
