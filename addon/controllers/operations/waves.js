import Controller from '@ember/controller';
import { action, get } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class OperationsWavesController extends Controller {
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
    @tracked table;
    @tracked isCreatingWave = false;

    /**
     * The vocabulary Wave::boot() documents and normalises to. The form offered a
     * free-text box, so anything typed there became a type the domain does not
     * recognise — the same fault already fixed on zones and bin locations.
     */
    waveTypes = ['standard', 'express', 'bulk'];

    @tracked newWave = { type: 'standard', priority: 5 };

    /**
     * Was a hand-rolled <table>: 65px rows of px-4 py-3, one line per pick list so
     * a wave with six was a six-line row, "No actions" printed as text, and no
     * search, sorting, pagination or column picker.
     */
    @tracked columns = [
        {
            label: this.intl.t('operations.waves.columns.wave'),
            valuePath: 'wave_number',
            cellComponent: 'click-to-copy',
            width: '150px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterParam: 'wave_number',
            filterComponent: 'filter/string',
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
            label: this.intl.t('operations.common.type'),
            valuePath: 'type',
            cellComponent: 'table/cell/base',
            width: '110px',
            resizable: true,
            sortable: true,
        },
        {
            label: this.intl.t('common.status'),
            valuePath: 'status',
            cellComponent: 'table/cell/status',
            width: '120px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterParam: 'status',
            filterComponent: 'filter/select',
            filterOptions: ['pending', 'released', 'in_progress', 'completed', 'cancelled'],
        },
        {
            label: this.intl.t('operations.waves.columns.pick-lists'),
            valuePath: 'pickListsSummary',
            cellComponent: 'table/cell/base',
            width: '130px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('operations.waves.columns.progress'),
            valuePath: 'progress',
            cellComponent: 'table/cell/base',
            width: '100px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('operations.common.priority'),
            valuePath: 'priority',
            cellComponent: 'cell/count',
            width: '90px',
            resizable: true,
            sortable: true,
            hidden: true,
        },
        {
            label: '',
            cellComponent: 'table/cell/dropdown',
            ddButtonText: false,
            ddButtonIcon: 'ellipsis-h',
            ddButtonIconPrefix: 'fas',
            ddMenuLabel: this.intl.t('operations.waves.actions-menu'),
            cellClassNames: 'overflow-visible',
            wrapperClass: 'flex items-center justify-end mx-2',
            width: '70px',
            actions: [
                {
                    label: this.intl.t('operations.common.start'),
                    icon: 'play',
                    fn: this.startWave,
                    isVisible: (wave) => ['pending', 'released'].includes(get(wave, 'status')),
                },
                {
                    label: this.intl.t('operations.waves.release'),
                    icon: 'paper-plane',
                    fn: this.releaseWave,
                    isVisible: (wave) => get(wave, 'status') === 'pending',
                },
                {
                    label: this.intl.t('operations.common.complete'),
                    icon: 'check',
                    fn: this.completeWave,
                    isVisible: (wave) => get(wave, 'status') === 'in_progress',
                },
                {
                    label: this.intl.t('operations.waves.no-actions'),
                    disabled: true,
                    isVisible: (wave) => ['completed', 'cancelled'].includes(get(wave, 'status')),
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

    @action startCreatingWave() {
        this.resetNewWave();
        this.isCreatingWave = true;
    }

    @action cancelCreatingWave() {
        this.isCreatingWave = false;
        this.resetNewWave();
    }

    resetNewWave() {
        this.newWave = { type: 'standard', priority: 5 };
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    @action setType(type) {
        this.newWave = { ...this.newWave, type };
    }

    @action setWaveWarehouse(warehouse) {
        this.newWave = {
            ...this.newWave,
            warehouse,
            warehouse_uuid: this.getRecordUuid(warehouse),
        };
    }

    @action async createWave() {
        try {
            if (!this.newWave.warehouse_uuid) {
                return this.notifications.warning('Select a warehouse for this wave.');
            }

            const priority = Number(this.newWave.priority ?? 5);
            if (!priority || priority <= 0) {
                return this.notifications.warning('Enter a priority greater than zero.');
            }

            const wave = this.store.createRecord('wave', {
                warehouse_uuid: this.newWave.warehouse_uuid,
                type: this.newWave.type ?? 'standard',
                priority,
                scheduled_at: this.newWave.scheduled_at,
                notes: this.newWave.notes,
                status: 'pending',
            });
            await wave.save();
            this.notifications.success('Wave created.');
            this.isCreatingWave = false;
            this.resetNewWave();
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    async updateWave(wave, actionName) {
        try {
            await this.fetch.post(`waves/${wave.public_id ?? wave.id}/${actionName}`, {}, { namespace: 'pallet/int/v1' });
            this.notifications.success(`Wave ${this.actionLabel(actionName)} successfully.`);
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action startWave(wave) {
        return this.updateWave(wave, 'start');
    }

    @action releaseWave(wave) {
        return this.updateWave(wave, 'release');
    }

    @action completeWave(wave) {
        return this.updateWave(wave, 'complete');
    }

    actionLabel(actionName) {
        return actionName === 'start' ? 'started' : `${actionName}d`;
    }
}
