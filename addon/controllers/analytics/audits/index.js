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
    @service intl;
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
        { label: this.intl.t('audit.all_events'), value: null },
        { label: this.intl.t('audit.event-types.stock_adjustment'), value: 'stock_adjustment' },
        { label: this.intl.t('audit.event-types.cycle_count'), value: 'cycle_count' },
        { label: this.intl.t('audit.event-types.po_received'), value: 'po_received' },
        { label: this.intl.t('audit.event-types.so_fulfilled'), value: 'so_fulfilled' },
        { label: this.intl.t('audit.event-types.stock_transfer'), value: 'stock_transfer' },
        { label: this.intl.t('audit.event-types.inventory_receive'), value: 'inventory_created' },
        { label: this.intl.t('audit.event-types.batch_created'), value: 'batch_created' },
    ];

    /**
     * Column definitions for the operational audit trail table.
     * Columns reflect the new schema: event_type, subject, action, reason, performed_by, date.
     *
     * @var {Array}
     */
    /**
     * SCREENS.md §G orders these Timestamp, Actor, Action, Resource type, Resource —
     * when and who first, because those are the two questions an audit trail exists to
     * answer. They were last, and the widths summed past the container, so on an
     * ordinary window the timestamp and the actor were the two columns pushed off the
     * right-hand edge.
     *
     * Widths are trimmed to fit rather than left to overflow. Before and after are not
     * columns at all: §G's must-never is a raw JSON blob in the row, so the diff lives
     * in the expandable row instead.
     */
    @tracked columns = [
        {
            label: this.intl.t('audit.columns.date'),
            valuePath: 'createdAt',
            width: '150px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/date',
        },
        {
            label: this.intl.t('audit.columns.performed-by'),
            valuePath: 'performedBy.name',
            width: '130px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('audit.columns.event'),
            valuePath: 'eventTypeLabel',
            cellComponent: 'table/cell/status',
            width: '150px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/select',
            filterOptions: [
                { label: this.intl.t('audit.event-types.stock_adjustment'), value: 'stock_adjustment' },
                { label: this.intl.t('audit.event-types.cycle_count'), value: 'cycle_count' },
                { label: this.intl.t('audit.event-types.po_received'), value: 'po_received' },
                { label: this.intl.t('audit.event-types.so_fulfilled'), value: 'so_fulfilled' },
                { label: this.intl.t('audit.event-types.stock_transfer'), value: 'stock_transfer' },
                { label: this.intl.t('audit.event-types.inventory_receive'), value: 'inventory_created' },
            ],
            filterParam: 'event_type',
        },
        {
            label: this.intl.t('audit.columns.action'),
            valuePath: 'action',
            width: '170px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('audit.columns.subject'),
            valuePath: 'subjectLabel',
            width: '110px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('audit.columns.subject-id'),
            valuePath: 'subject_reference',
            width: '160px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('audit.columns.reason'),
            valuePath: 'reason',
            width: '120px',
            resizable: true,
            sortable: false,
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
