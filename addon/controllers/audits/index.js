import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { isBlank } from '@ember/utils';
import { timeout } from 'ember-concurrency';
import { task } from 'ember-concurrency-decorators';

/**
 * AuditsIndexController
 *
 * Displays the WMS operational audit trail. This is a read-only view of
 * intentional warehouse events (stock adjustments, cycle counts, PO receipts,
 * SO fulfilments, stock transfers). It is NOT a generic data-change log —
 * that is handled by Spatie Activity Log at the framework level.
 */
export default class AuditsIndexController extends Controller {
    /**
     * @service notifications
     */
    @service notifications;

    /**
     * @service store
     */
    @service store;

    /**
     * @service filters
     */
    @service filters;

    /**
     * @service hostRouter
     */
    @service hostRouter;

    /**
     * @service fetch
     */
    @service fetch;

    /**
     * Queryable parameters for this controller's model.
     * @var {Array}
     */
    queryParams = ['page', 'limit', 'sort', 'query', 'event_type', 'auditable_type'];

    /** @tracked page = 1 */
    @tracked page = 1;

    /** @tracked limit */
    @tracked limit;

    /** @tracked sort = '-created_at' */
    @tracked sort = '-created_at';

    /** @tracked query — free-text search */
    @tracked query;

    /**
     * Filter by WMS event type (e.g. 'stock_adjustment', 'cycle_count').
     * @var {String}
     */
    @tracked event_type;

    /**
     * Filter by the subject model class (e.g. 'Inventory', 'PurchaseOrder').
     * @var {String}
     */
    @tracked auditable_type;

    /**
     * Reference to the rendered table component.
     * @var {Object}
     */
    @tracked table;

    /**
     * Available event type filter options, matching AuditEventType constants.
     * @var {Array}
     */
    eventTypeOptions = [
        { label: 'All Events', value: null },
        { label: 'Stock Adjustment', value: 'stock_adjustment' },
        { label: 'Cycle Count', value: 'cycle_count' },
        { label: 'PO Received', value: 'po_received' },
        { label: 'SO Fulfilled', value: 'so_fulfilled' },
        { label: 'Stock Transfer', value: 'stock_transfer' },
        { label: 'Inventory Received', value: 'inventory_created' },
        { label: 'Batch Created', value: 'batch_created' },
    ];

    /**
     * Column definitions for the operational audit trail table.
     * Columns reflect the new schema: event_type, subject, action, reason, performed_by, date.
     *
     * @var {Array}
     */
    @tracked columns = [
        {
            label: 'Event',
            valuePath: 'eventTypeLabel',
            cellComponent: 'table/cell/status',
            width: '160px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/select',
            filterOptions: [
                { label: 'Stock Adjustment', value: 'stock_adjustment' },
                { label: 'Cycle Count', value: 'cycle_count' },
                { label: 'PO Received', value: 'po_received' },
                { label: 'SO Fulfilled', value: 'so_fulfilled' },
                { label: 'Stock Transfer', value: 'stock_transfer' },
                { label: 'Inventory Received', value: 'inventory_created' },
            ],
            filterParam: 'event_type',
        },
        {
            label: 'Action',
            valuePath: 'action',
            width: '140px',
            resizable: true,
            sortable: false,
        },
        {
            label: 'Subject',
            valuePath: 'subjectLabel',
            width: '140px',
            resizable: true,
            sortable: false,
        },
        {
            label: 'Subject ID',
            valuePath: 'auditable_uuid',
            width: '200px',
            resizable: true,
            sortable: false,
        },
        {
            label: 'Reason',
            valuePath: 'reason',
            width: '200px',
            resizable: true,
            sortable: false,
        },
        {
            label: 'Performed By',
            valuePath: 'performedBy.name',
            width: '160px',
            resizable: true,
            sortable: false,
        },
        {
            label: 'Date',
            valuePath: 'createdAt',
            width: '160px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/date',
        },
    ];

    /**
     * Search task — debounced free-text search.
     * @void
     */
    @task({ restartable: true }) *search({ target: { value } }) {
        if (isBlank(value)) {
            this.query = null;
            return;
        }
        yield timeout(250);
        if (this.page > 1) {
            this.page = 1;
        }
        this.query = value;
    }

    /**
     * Filter by event type from the dropdown.
     * @param {String|null} value
     */
    @action filterByEventType(value) {
        this.event_type = value || null;
        this.page = 1;
    }

    /**
     * Clear all active filters.
     */
    @action clearFilters() {
        this.event_type = null;
        this.auditable_type = null;
        this.query = null;
        this.page = 1;
    }
}
