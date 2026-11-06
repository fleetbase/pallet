import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class InventoryBatchesController extends Controller {
    @service intl;

    queryParams = ['page', 'limit', 'sort', 'query'];

    @tracked page = 1;
    @tracked limit;
    /*
     * FEFO — first expired, first out. SCREENS.md §D: "this is the screen where FEFO
     * becomes visible", so it must never default-sort by created date. Ascending, so
     * whatever needs using or writing off soonest is at the top.
     */
    @tracked sort = 'expiry_date_at';
    @tracked query;
    @tracked table;

    /**
     * Was a raw <table> with px-4 py-3 cells and no search, sorting, pagination
     * or column picker — the screen had no controller at all.
     */
    @tracked columns = [
        {
            label: this.intl.t('inventory.fields.batch-number'),
            valuePath: 'batch_number',
            cellComponent: 'click-to-copy',
            width: '180px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterParam: 'batch_number',
            filterComponent: 'filter/string',
        },
        {
            label: this.intl.t('inventory.fields.product'),
            valuePath: 'product.name',
            cellComponent: 'table/cell/base',
            width: '200px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('inventory.fields.variant'),
            valuePath: 'variant.display_name',
            cellComponent: 'table/cell/base',
            width: '150px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('inventory.fields.quantity'),
            valuePath: 'quantity',
            cellComponent: 'cell/count',
            width: '90px',
            resizable: true,
            sortable: true,
        },
        {
            label: this.intl.t('inventory.fields.manufactured'),
            valuePath: 'manufacturedAt',
            sortParam: 'manufacture_date_at',
            cellComponent: 'table/cell/base',
            width: '140px',
            resizable: true,
            sortable: true,
        },
        {
            label: this.intl.t('inventory.fields.expiry-date'),
            valuePath: 'expiresAt',
            sortParam: 'expiry_date_at',
            cellComponent: 'table/cell/base',
            width: '140px',
            resizable: true,
            sortable: true,
        },
        {
            // A date alone makes the reader do the arithmetic against today on every
            // row. Negative once the batch has expired.
            label: this.intl.t('inventory.fields.days-to-expiry'),
            valuePath: 'daysToExpiry',
            cellComponent: 'cell/count',
            width: '120px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('common.status'),
            valuePath: 'expiryStatus',
            cellComponent: 'cell/expiry-status',
            width: '130px',
            resizable: true,
            sortable: false,
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
}
