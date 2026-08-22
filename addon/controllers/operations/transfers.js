import Controller from '@ember/controller';
import { action, get } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class OperationsTransfersController extends Controller {
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

    @tracked newTransfer = {};
    @tracked isCreatingTransfer = false;

    @action startCreatingTransfer() {
        this.resetNewTransfer();
        this.isCreatingTransfer = true;
    }

    @action cancelCreatingTransfer() {
        this.isCreatingTransfer = false;
        this.resetNewTransfer();
    }

    /**
     * Was a hand-rolled <table> with px-4 py-3 cells, no search, no sorting, no
     * pagination and a two-line transfer number. These columns put it on the same
     * Layout::Resource::Tabular every other list in the module uses.
     */
    @tracked columns = [
        {
            label: this.intl.t('operations.transfers.columns.transfer'),
            valuePath: 'transfer_number',
            cellComponent: 'click-to-copy',
            width: '160px',
            resizable: true,
            sortable: true,
            filterable: true,
            filterParam: 'transfer_number',
            filterComponent: 'filter/string',
        },
        {
            label: this.intl.t('operations.transfers.from-warehouse'),
            valuePath: 'fromWarehouse.name',
            cellComponent: 'table/cell/base',
            width: '160px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('operations.transfers.to-warehouse'),
            valuePath: 'toWarehouse.name',
            cellComponent: 'table/cell/base',
            width: '160px',
            resizable: true,
            sortable: false,
        },
        {
            // the old cell stacked one line per item, so a transfer of four
            // products was a four-line row; the count carries the same fact
            label: this.intl.t('operations.transfers.columns.items'),
            valuePath: 'itemsSummary',
            cellComponent: 'table/cell/base',
            width: '180px',
            resizable: true,
            sortable: false,
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
            filterOptions: ['pending', 'approved', 'in_transit', 'completed', 'cancelled'],
        },
        {
            label: this.intl.t('operations.common.quantity'),
            valuePath: 'total_quantity',
            cellComponent: 'cell/count',
            width: '90px',
            resizable: true,
            sortable: true,
        },
        {
            label: this.intl.t('common.created-at'),
            valuePath: 'createdAt',
            sortParam: 'created_at',
            cellComponent: 'table/cell/base',
            width: '140px',
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
            ddMenuLabel: this.intl.t('operations.transfers.actions-menu'),
            cellClassNames: 'overflow-visible',
            wrapperClass: 'flex items-center justify-end mx-2',
            width: '70px',
            // the old table printed the literal words "No actions" in this column
            // for every completed transfer; an empty menu says the same thing
            actions: [
                {
                    label: this.intl.t('operations.common.approve'),
                    icon: 'thumbs-up',
                    fn: this.approveTransfer,
                    isVisible: (transfer) => get(transfer, 'status') === 'pending',
                },
                {
                    label: this.intl.t('operations.transfers.ship'),
                    icon: 'truck',
                    fn: this.shipTransfer,
                    isVisible: (transfer) => get(transfer, 'status') === 'approved',
                },
                {
                    label: this.intl.t('operations.transfers.receive'),
                    icon: 'box-open',
                    fn: this.receiveTransfer,
                    isVisible: (transfer) => get(transfer, 'status') === 'in_transit',
                },
                {
                    label: this.intl.t('common.cancel'),
                    icon: 'ban',
                    class: 'text-danger',
                    fn: this.cancelTransfer,
                    isVisible: (transfer) => ['pending', 'approved'].includes(get(transfer, 'status')),
                },
                {
                    // a completed or cancelled transfer has no lifecycle left, and an
                    // ellipsis that opens an empty popover is a dead control
                    label: this.intl.t('operations.transfers.no-actions'),
                    disabled: true,
                    isVisible: (transfer) => ['completed', 'cancelled'].includes(get(transfer, 'status')),
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

    resetNewTransfer() {
        this.newTransfer = {};
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    @action setFromWarehouse(warehouse) {
        this.newTransfer = {
            ...this.newTransfer,
            fromWarehouse: warehouse,
            from_warehouse_uuid: this.getRecordUuid(warehouse),
        };
    }

    @action setToWarehouse(warehouse) {
        this.newTransfer = {
            ...this.newTransfer,
            toWarehouse: warehouse,
            to_warehouse_uuid: this.getRecordUuid(warehouse),
        };
    }

    @action setTransferProduct(product) {
        this.newTransfer = {
            ...this.newTransfer,
            product,
            product_uuid: this.getRecordUuid(product),
            variant: null,
            variant_uuid: null,
        };
    }

    @action setTransferVariant(variant) {
        this.newTransfer = {
            ...this.newTransfer,
            variant,
            variant_uuid: this.getRecordUuid(variant),
        };
    }

    @action async createTransfer() {
        try {
            if (!this.newTransfer.from_warehouse_uuid || !this.newTransfer.to_warehouse_uuid) {
                return this.notifications.warning('Select both source and destination warehouses.');
            }

            if (this.newTransfer.from_warehouse_uuid === this.newTransfer.to_warehouse_uuid) {
                return this.notifications.warning('Source and destination warehouses must be different.');
            }

            if (!this.newTransfer.product_uuid) {
                return this.notifications.warning('Select a product to transfer.');
            }

            if (this.newTransfer.product?.has_variants && !this.newTransfer.variant_uuid) {
                return this.notifications.warning('Select a variant for this product.');
            }

            const quantity = Number(this.newTransfer.quantity ?? 0);
            if (!quantity || quantity <= 0) {
                return this.notifications.warning('Enter a transfer quantity greater than zero.');
            }

            const transfer = this.store.createRecord('stock-transfer', {
                from_warehouse_uuid: this.newTransfer.from_warehouse_uuid,
                to_warehouse_uuid: this.newTransfer.to_warehouse_uuid,
                type: this.newTransfer.type ?? 'standard',
                status: 'pending',
                notes: this.newTransfer.notes,
            });
            const savedTransfer = await transfer.save();

            const item = this.store.createRecord('stock-transfer-item', {
                stock_transfer_uuid: this.getRecordUuid(savedTransfer),
                product_uuid: this.newTransfer.product_uuid,
                variant_uuid: this.newTransfer.variant_uuid,
                quantity,
            });
            await item.save();

            this.notifications.success('Stock transfer created.');
            this.isCreatingTransfer = false;
            this.resetNewTransfer();
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    async updateTransfer(transfer, actionName) {
        try {
            await this.fetch.post(`stock-transfers/${transfer.public_id ?? transfer.id}/${actionName}`, {}, { namespace: 'pallet/int/v1' });
            this.notifications.success(`Stock transfer ${this.actionLabel(actionName)} successfully.`);
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action approveTransfer(transfer) {
        return this.updateTransfer(transfer, 'approve');
    }

    @action shipTransfer(transfer) {
        return this.updateTransfer(transfer, 'ship');
    }

    @action receiveTransfer(transfer) {
        return this.updateTransfer(transfer, 'receive');
    }

    @action cancelTransfer(transfer) {
        return this.updateTransfer(transfer, 'cancel');
    }

    actionLabel(actionName) {
        if (actionName === 'ship') {
            return 'shipped';
        }

        if (actionName === 'cancel') {
            return 'cancelled';
        }

        return `${actionName}d`;
    }
}
