import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class LocationsIndexController extends Controller {
    @service binLocationActions;
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
                onClick: this.binLocationActions.refresh,
                helpText: this.intl.t('common.refresh'),
            },
            {
                text: this.intl.t('common.new'),
                type: 'primary',
                icon: 'plus',
                onClick: this.binLocationActions.transition.create,
            },
            {
                text: this.intl.t('common.export'),
                icon: 'long-arrow-up',
                iconClass: 'rotate-icon-45',
                wrapperClass: 'hidden md:flex',
                onClick: this.binLocationActions.export,
            },
        ];
    }

    get bulkActions() {
        const selected = this.tableContext.getSelectedRows();

        return [
            {
                label: this.intl.t('common.delete-selected-count', { count: selected.length }),
                class: 'text-red-500',
                fn: this.binLocationActions.bulkDelete,
            },
        ];
    }

    get columns() {
        return [
            {
                label: this.intl.t('facilities.locations.bin'),
                valuePath: 'bin_number',
                width: '150px',
                cellComponent: 'table/cell/anchor',
                action: this.binLocationActions.transition.view,
                resizable: true,
                sortable: true,
                filterable: true,
                filterParam: 'bin_number',
                filterComponent: 'filter/string',
            },
            {
                label: this.intl.t('operations.common.warehouse'),
                valuePath: 'warehouse.name',
                width: '210px',
                cellComponent: 'table/cell/anchor',
                action: this.binLocationActions.transition.view,
                resizable: true,
                sortable: false,
                filterable: false,
            },
            {
                label: this.intl.t('operations.cycle-counts.zone'),
                valuePath: 'zone.name',
                width: '160px',
                cellComponent: 'table/cell/base',
                resizable: true,
                sortable: false,
                filterable: false,
            },
            {
                label: this.intl.t('common.type'),
                valuePath: 'type',
                width: '120px',
                cellComponent: 'table/cell/base',
                humanize: true,
                resizable: true,
                sortable: true,
                filterable: true,
                filterParam: 'type',
                filterComponent: 'filter/string',
            },
            {
                label: this.intl.t('common.status'),
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
                label: this.intl.t('warehouse.fields.capacity'),
                valuePath: 'capacity',
                width: '100px',
                cellComponent: 'cell/count',
                resizable: true,
                sortable: true,
                filterable: false,
            },
            {
                label: this.intl.t('facilities.locations.available-capacity'),
                valuePath: 'available_capacity',
                width: '110px',
                cellComponent: 'cell/count',
                resizable: true,
                sortable: false,
                filterable: false,
            },
            {
                label: this.intl.t('common.created-at'),
                valuePath: 'createdAt',
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
                ddMenuLabel: this.intl.t('common.resource-actions', { resource: 'Bin Location' }),
                cellClassNames: 'overflow-visible',
                wrapperClass: 'flex items-center justify-end mx-2',
                width: '90px',
                actions: [
                    {
                        label: this.intl.t('common.view-resource', { resource: 'Bin Location' }),
                        fn: this.binLocationActions.transition.view,
                    },
                    {
                        label: this.intl.t('common.edit-resource', { resource: 'Bin Location' }),
                        fn: this.binLocationActions.transition.edit,
                    },
                    {
                        separator: true,
                    },
                    {
                        label: this.intl.t('common.delete-resource', { resource: 'Bin Location' }),
                        fn: this.binLocationActions.delete,
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
