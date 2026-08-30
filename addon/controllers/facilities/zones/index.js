import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class ZonesIndexController extends Controller {
    @service warehouseZoneActions;
    @service tableContext;
    @service intl;

    queryParams = ['page', 'limit', 'sort', 'query', 'status', 'type', 'created_at', 'updated_at'];

    @tracked page = 1;
    @tracked limit;
    @tracked sort = '-created_at';
    @tracked query;
    @tracked status;
    @tracked type;
    @tracked table;

    get actionButtons() {
        return [
            {
                icon: 'refresh',
                onClick: this.warehouseZoneActions.refresh,
                helpText: this.intl.t('common.refresh'),
            },
            {
                text: this.intl.t('common.new'),
                type: 'primary',
                icon: 'plus',
                onClick: this.warehouseZoneActions.transition.create,
            },
            {
                text: this.intl.t('common.export'),
                icon: 'long-arrow-up',
                iconClass: 'rotate-icon-45',
                wrapperClass: 'hidden md:flex',
                onClick: this.warehouseZoneActions.export,
            },
        ];
    }

    get bulkActions() {
        const selected = this.tableContext.getSelectedRows();

        return [
            {
                label: this.intl.t('common.delete-selected-count', { count: selected.length }),
                class: 'text-red-500',
                fn: this.warehouseZoneActions.bulkDelete,
            },
        ];
    }

    get columns() {
        return [
            {
                label: 'Zone',
                valuePath: 'name',
                width: '180px',
                cellComponent: 'table/cell/anchor',
                action: this.warehouseZoneActions.transition.view,
                resizable: true,
                sortable: true,
                filterable: true,
                filterParam: 'name',
                filterComponent: 'filter/string',
            },
            {
                label: 'Code',
                valuePath: 'code',
                width: '110px',
                cellComponent: 'table/cell/base',
                resizable: true,
                sortable: true,
                filterable: true,
                filterParam: 'code',
                filterComponent: 'filter/string',
            },
            {
                label: 'Warehouse',
                valuePath: 'warehouse.name',
                width: '220px',
                cellComponent: 'table/cell/anchor',
                action: this.warehouseZoneActions.transition.view,
                resizable: true,
                sortable: false,
                filterable: false,
            },
            {
                label: 'Type',
                valuePath: 'type',
                width: '130px',
                cellComponent: 'table/cell/base',
                humanize: true,
                resizable: true,
                sortable: true,
                filterable: true,
                filterParam: 'type',
                filterComponent: 'filter/string',
            },
            {
                label: 'Status',
                valuePath: 'status',
                width: '110px',
                cellComponent: 'table/cell/status',
                resizable: true,
                sortable: true,
                filterable: true,
                filterParam: 'status',
                filterComponent: 'filter/string',
            },
            {
                label: 'Capacity',
                valuePath: 'capacity',
                width: '100px',
                cellComponent: 'cell/count',
                resizable: true,
                sortable: true,
                filterable: false,
            },
            {
                label: 'Utilization',
                valuePath: 'utilization_percentage',
                width: '110px',
                cellComponent: 'cell/percentage',
                resizable: true,
                sortable: false,
                filterable: false,
            },
            {
                label: 'Created At',
                valuePath: 'created_at',
                sortParam: 'created_at',
                width: '140px',
                cellComponent: 'table/cell/base',
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/date',
            },
            {
                label: '',
                cellComponent: 'table/cell/dropdown',
                ddButtonText: false,
                ddButtonIcon: 'ellipsis-h',
                ddButtonIconPrefix: 'fas',
                ddMenuLabel: this.intl.t('common.resource-actions', { resource: 'Zone' }),
                cellClassNames: 'overflow-visible',
                wrapperClass: 'flex items-center justify-end mx-2',
                width: '90px',
                actions: [
                    {
                        label: this.intl.t('common.view-resource', { resource: 'Zone' }),
                        fn: this.warehouseZoneActions.transition.view,
                    },
                    {
                        label: this.intl.t('common.edit-resource', { resource: 'Zone' }),
                        fn: this.warehouseZoneActions.transition.edit,
                    },
                    {
                        separator: true,
                    },
                    {
                        label: this.intl.t('common.delete-resource', { resource: 'Zone' }),
                        fn: this.warehouseZoneActions.delete,
                    },
                ],
                sortable: false,
                filterable: false,
                resizable: false,
                searchable: false,
            },
        ];
    }

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
}
