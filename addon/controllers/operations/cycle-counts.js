import Controller from '@ember/controller';
import { action, get } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class OperationsCycleCountsController extends Controller {
    @service currentUser;
    @service fetch;
    @service hostRouter;
    @service intl;
    @service notifications;
    @service store;

    queryParams = ['page', 'limit', 'sort', 'query', 'status'];

    @tracked page = 1;
    @tracked limit;
    @tracked sort = '-created_at';
    @tracked query;
    @tracked status;
    @tracked isCreatingCycleCount = false;

    /**
     * The count sheet — the nested items table where quantities are typed — is
     * genuinely the working surface here, so unlike the other operations screens
     * this one drives Table directly for @canExpand. Layout::Resource::Tabular
     * cannot host it: its only block replaces the Table.
     */
    @tracked columns = [
        {
            label: this.intl.t('operations.cycle-counts.columns.count'),
            valuePath: 'count_number',
            cellComponent: 'click-to-copy',
            width: '170px',
            resizable: true,
            sortable: true,
        },
        {
            label: this.intl.t('operations.common.warehouse'),
            valuePath: 'warehouse.name',
            cellComponent: 'table/cell/base',
            width: '170px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('operations.cycle-counts.zone'),
            valuePath: 'zone.name',
            cellComponent: 'table/cell/base',
            width: '140px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('operations.cycle-counts.columns.assigned-to'),
            valuePath: 'assignedTo.name',
            cellComponent: 'table/cell/base',
            width: '150px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('common.status'),
            valuePath: 'status',
            cellComponent: 'table/cell/status',
            width: '130px',
            resizable: true,
            sortable: true,
        },
        {
            label: this.intl.t('operations.cycle-counts.columns.accuracy'),
            valuePath: 'accuracy_percentage',
            cellComponent: 'cell/percentage',
            width: '100px',
            resizable: true,
            sortable: false,
        },
        {
            label: '',
            cellComponent: 'table/cell/dropdown',
            ddButtonText: false,
            ddButtonIcon: 'ellipsis-h',
            ddButtonIconPrefix: 'fas',
            ddMenuLabel: this.intl.t('operations.cycle-counts.actions-menu'),
            cellClassNames: 'overflow-visible',
            wrapperClass: 'flex items-center justify-end mx-2',
            width: '70px',
            actions: [
                {
                    label: this.intl.t('operations.common.start'),
                    icon: 'play',
                    fn: this.startCycleCount,
                    isVisible: (count) => get(count, 'status') === 'pending',
                },
                {
                    label: this.intl.t('operations.common.complete'),
                    icon: 'check',
                    fn: this.completeCycleCount,
                    isVisible: (count) => get(count, 'status') === 'in_progress',
                },
                {
                    label: this.intl.t('operations.common.approve'),
                    icon: 'thumbs-up',
                    fn: this.approveCycleCount,
                    isVisible: (count) => get(count, 'status') === 'completed',
                },
                {
                    label: this.intl.t('operations.cycle-counts.no-actions'),
                    disabled: true,
                    isVisible: (count) => ['approved', 'cancelled'].includes(get(count, 'status')),
                },
            ],
            sortable: false,
            filterable: false,
            resizable: false,
            searchable: false,
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

    @action startCreatingCycleCount() {
        this.isCreatingCycleCount = true;
    }

    @action cancelCreatingCycleCount() {
        this.isCreatingCycleCount = false;
    }

    /**
     * The vocabulary CycleCount::boot() documents and normalises to. The form offered a
     * free-text box, so anything typed there became a type the domain does not
     * recognise — the same fault already fixed on zones and bin locations.
     */
    cycleCountTypes = ['standard', 'full', 'spot', 'abc'];

    @tracked newCycleCount = { type: 'standard' };

    resetNewCycleCount() {
        this.newCycleCount = { type: 'standard' };
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    get currentUserUuid() {
        return this.currentUser.user?.uuid ?? this.currentUser.user?.id ?? this.currentUser.uuid ?? this.currentUser.id;
    }

    @action setType(type) {
        this.newCycleCount = { ...this.newCycleCount, type };
    }

    @action setCycleCountWarehouse(warehouse) {
        this.newCycleCount = {
            ...this.newCycleCount,
            warehouse,
            warehouse_uuid: this.getRecordUuid(warehouse),
            zone: null,
            zone_uuid: null,
        };
    }

    @action setCycleCountZone(zone) {
        this.newCycleCount = {
            ...this.newCycleCount,
            zone,
            zone_uuid: this.getRecordUuid(zone),
        };
    }

    @action async createCycleCount() {
        try {
            if (!this.newCycleCount.warehouse_uuid) {
                return this.notifications.warning('Select a warehouse for this cycle count.');
            }

            const cycleCount = this.store.createRecord('cycle-count', {
                warehouse_uuid: this.newCycleCount.warehouse_uuid,
                zone_uuid: this.newCycleCount.zone_uuid,
                type: this.newCycleCount.type ?? 'standard',
                scheduled_at: this.newCycleCount.scheduled_at,
                notes: this.newCycleCount.notes,
                status: 'pending',
            });
            await cycleCount.save();
            this.notifications.success('Cycle count created.');
            this.isCreatingCycleCount = false;
            this.resetNewCycleCount();
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    async updateCycleCount(cycleCount, actionName) {
        try {
            await this.fetch.post(`cycle-counts/${cycleCount.public_id ?? cycleCount.id}/${actionName}`, {}, { namespace: 'pallet/int/v1' });
            this.notifications.success(`Cycle count ${this.actionLabel(actionName)} successfully.`);
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action startCycleCount(cycleCount) {
        return this.updateCycleCount(cycleCount, 'start');
    }

    @action completeCycleCount(cycleCount) {
        return this.updateCycleCount(cycleCount, 'complete');
    }

    @action approveCycleCount(cycleCount) {
        return this.updateCycleCount(cycleCount, 'approve');
    }

    @action async recordCycleCountItem(item) {
        const countedQuantity = Number(item.counted_quantity ?? 0);

        if (countedQuantity < 0) {
            return this.notifications.warning('Enter a counted quantity of zero or greater.');
        }

        try {
            await this.fetch.post(
                `cycle-count-items/${item.public_id ?? item.id}/record-count`,
                {
                    counted_quantity: countedQuantity,
                    counted_by_uuid: this.currentUserUuid,
                },
                { namespace: 'pallet/int/v1' }
            );
            this.notifications.success('Cycle count item recorded.');
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    actionLabel(actionName) {
        return actionName === 'start' ? 'started' : `${actionName}d`;
    }
}
