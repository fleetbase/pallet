import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class BatchIndexController extends Controller {
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
    queryParams = ['page', 'limit', 'sort', 'product', 'warehouse', 'batch'];

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
    @tracked columns = [
        {
            label: 'Product',
            valuePath: 'product.name',
            action: this.viewBatch,
            width: '200px',
            cellComponent: 'table/cell/anchor',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/string',
        },
        {
            label: 'Quantity',
            valuePath: 'quantity',
            width: '200px',
        },
        {
            label: 'Batch Number',
            valuePath: 'batch_number',
            width: '200px',
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
            ddMenuLabel: 'Batch Actions',
            cellClassNames: 'overflow-visible',
            wrapperClass: 'flex items-center justify-end mx-2',
            width: '10%',
            actions: [
                {
                    label: 'View Batch',
                    fn: this.viewBatch,
                },
                {
                    label: 'Edit Batch',
                    fn: this.editBatch,
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
     * Toggles dialog to export `batch`
     *
     * @void
     */
    @action exportProcuts() {
        this.crud.export('batch');
    }

    /**
     * View a `batch` details in overlay
     *
     * @param {BatchModel} batch
     * @param {Object} options
     * @void
     */
    @action viewBatch(batch) {
        return this.transitionToRoute('batch.index.details', batch);
    }

    /**
     * Create a new `batch` in modal
     *
     * @param {Object} options
     * @void
     */
    @action createBatch() {
        return this.transitionToRoute('batch.index.new');
    }

    /**
     * Edit a `batch` details
     *
     * @param {BatchModel} batch
     * @param {Object} options
     * @void
     */
    @action async editBatch(batch) {
        return this.transitionToRoute('batch.index.edit', batch);
    }

    /**
     * Delete a `batch` via confirm prompt
     *
     * @param {BatchModel} batch
     * @param {Object} options
     * @void
     */
    @action deleteBatch(batch, options = {}) {
        this.crud.delete(batch, {
            onConfirm: () => {
                return this.hostRouter.refresh();
            },
            ...options,
        });
    }

    /**
     * Bulk deletes selected `batch` via confirm prompt
     *
     * @param {Array} selected an array of selected models
     * @void
     */
    @action bulkDeleteBatchs() {
        const selected = this.table.selectedRows;

        this.crud.bulkDelete(selected, {
            modelNamePath: `public_id`,
            acceptButtonText: 'Delete Batches',
            fetchOptions: {
                namespace: 'pallet/int/v1',
            },
            onSuccess: () => {
                return this.hostRouter.refresh();
            },
        });
    }
}
