import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class InventoryAdjustmentsController extends Controller {
    @service hostRouter;
    @service intl;

    queryParams = ['page', 'limit', 'sort', 'query'];

    @tracked page = 1;
    @tracked limit;
    @tracked sort = '-created_at';
    @tracked query;
    @tracked table;

    /**
     * Was a raw <table> with px-4 py-3 cells and nine always-visible columns, no
     * search, sorting, pagination or column picker. Before and after are the
     * audit detail; the delta is what a reader scans for, so that stays visible
     * and the other two are a column-picker away.
     */
    @tracked columns = [
        {
            // an adjustment is an immutable audit row and outlives what it points
            // at; four rows here reference a product soft-deleted a day later, and
            // rendering them as a line of dashes reads as an incomplete record
            // rather than one whose subject is gone
            label: this.intl.t('inventory.fields.product'),
            valuePath: 'product.name',
            uuidPath: 'product_uuid',
            relationPath: 'product',
            missingLabel: this.intl.t('inventory.product-unavailable'),
            cellComponent: 'cell/related-record',
            width: '200px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('inventory.fields.variant'),
            valuePath: 'variant.display_name',
            uuidPath: 'variant_uuid',
            relationPath: 'variant',
            namePath: 'display_name',
            missingLabel: this.intl.t('inventory.variant-unavailable'),
            cellComponent: 'cell/related-record',
            width: '150px',
            resizable: true,
            sortable: false,
            hidden: true,
        },
        {
            label: this.intl.t('inventory.fields.warehouse'),
            valuePath: 'warehouse.name',
            uuidPath: 'warehouse_uuid',
            relationPath: 'warehouse',
            missingLabel: this.intl.t('inventory.warehouse-unavailable'),
            cellComponent: 'cell/related-record',
            width: '170px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('common.type'),
            valuePath: 'type',
            cellComponent: 'table/cell/status',
            width: '120px',
            resizable: true,
            sortable: true,
        },
        {
            label: this.intl.t('inventory.adjustments.delta'),
            valuePath: 'quantity',
            cellComponent: 'cell/count',
            width: '90px',
            resizable: true,
            sortable: true,
        },
        {
            label: this.intl.t('inventory.adjustments.before'),
            valuePath: 'before_quantity',
            cellComponent: 'cell/count',
            width: '90px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('inventory.adjustments.after'),
            valuePath: 'after_quantity',
            cellComponent: 'cell/count',
            width: '90px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('inventory.adjustments.reason'),
            valuePath: 'reason',
            cellComponent: 'table/cell/base',
            width: '200px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('common.created-at'),
            valuePath: 'createdAt',
            sortParam: 'created_at',
            cellComponent: 'table/cell/base',
            width: '160px',
            resizable: true,
            sortable: true,
        },
    ];

    @task({ restartable: true }) *search({ target: { value } }) {
        if (isBlank(value)) {
            this.query = null;
            return;
        }

        if (this.page > 1) {
            this.page = 1;
        }

        yield timeout(250);
        this.query = value;
    }

    @action createAdjustment() {
        return this.hostRouter.transitionTo('console.pallet.inventory.index.new-stock-adjustment');
    }
}
