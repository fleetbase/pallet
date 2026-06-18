import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';

export default class OperationsTransfersController extends Controller {
    @service fetch;
    @service hostRouter;
    @service notifications;
    @service store;

    @tracked newTransfer = {};

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
