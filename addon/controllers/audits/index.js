import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { isBlank } from '@ember/utils';
import { timeout } from 'ember-concurrency';
import { task } from 'ember-concurrency-decorators';

export default class AuditsIndexController extends Controller {
    /**
     * Inject the `notifications` service
     *
     * @var {Service}
     */
    @service notifications;

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
    queryParams = ['page', 'limit', 'sort', 'query', 'action', 'auditable_type'];

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
     * The filterable param `query`
     *
     * @var {String}
     */
    @tracked query;

    /**
     * The filterable param `action`
     *
     * @var {String}
     */
    @tracked action;

    /**
     * The filterable param `auditable_type`
     *
     * @var {String}
     */
    @tracked auditable_type;

    /**
     * Reference to the table component
     *
     * @var {Object}
     */
    @tracked table;

    /**
     * The columns for the audit table.
     *
     * @var {Array}
     */
    @tracked columns = [
        {
            label: 'Performed By',
            valuePath: 'performedBy.name',
            width: '160px',
            resizable: true,
            sortable: false,
        },
        {
            label: 'Action',
            valuePath: 'action',
            cellComponent: 'table/cell/status',
            width: '120px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterComponent: 'filter/string',
        },
        {
            label: 'Resource Type',
            valuePath: 'resourceLabel',
            width: '160px',
            resizable: true,
            sortable: false,
        },
        {
            label: 'Resource ID',
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
