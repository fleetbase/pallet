import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';

export default class OperationsPickListsController extends Controller {
    @service currentUser;
    @service fetch;
    @service hostRouter;
    @service notifications;
    @service store;

    @tracked newPickList = { type: 'discrete', priority: 5 };
    @tracked newPickItem = { quantity_requested: 1 };

    resetNewPickList() {
        this.newPickList = { type: 'discrete', priority: 5 };
    }

    resetNewPickItem() {
        this.newPickItem = { quantity_requested: 1 };
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    get currentUserUuid() {
        return this.currentUser.user?.uuid ?? this.currentUser.user?.id ?? this.currentUser.uuid ?? this.currentUser.id;
    }

    @action setPickListWarehouse(warehouse) {
        this.newPickList = {
            ...this.newPickList,
            warehouse,
            warehouse_uuid: this.getRecordUuid(warehouse),
        };
    }

    @action setPickListWave(wave) {
        this.newPickList = {
            ...this.newPickList,
            wave,
            wave_uuid: this.getRecordUuid(wave),
        };
    }

    @action setPickItemProduct(product) {
        this.newPickItem = {
            ...this.newPickItem,
            product,
            product_uuid: this.getRecordUuid(product),
            variant: null,
            variant_uuid: null,
        };
    }

    @action setPickItemVariant(variant) {
        this.newPickItem = {
            ...this.newPickItem,
            variant,
            variant_uuid: this.getRecordUuid(variant),
        };
    }

    @action setPickItemInventory(inventory) {
        this.newPickItem = {
            ...this.newPickItem,
            inventory,
            inventory_uuid: this.getRecordUuid(inventory),
        };
    }

    @action setPickItemBinLocation(binLocation) {
        this.newPickItem = {
            ...this.newPickItem,
            binLocation,
            bin_location_uuid: this.getRecordUuid(binLocation),
        };
    }

    @action async createPickList() {
        try {
            if (!this.newPickList.warehouse_uuid) {
                return this.notifications.warning('Select a warehouse for this pick list.');
            }

            const priority = Number(this.newPickList.priority ?? 5);
            if (!priority || priority <= 0) {
                return this.notifications.warning('Enter a priority greater than zero.');
            }

            const pickList = this.store.createRecord('pick-list', {
                warehouse_uuid: this.newPickList.warehouse_uuid,
                wave_uuid: this.newPickList.wave_uuid,
                type: this.newPickList.type ?? 'discrete',
                priority,
                notes: this.newPickList.notes,
                status: 'pending',
            });
            await pickList.save();
            this.notifications.success('Pick list created.');
            this.resetNewPickList();
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action async createPickListItem() {
        try {
            if (!this.newPickItem.pickList) {
                return this.notifications.warning('Select a pick list for this item.');
            }

            if (!this.newPickItem.product_uuid) {
                return this.notifications.warning('Select a product for this pick item.');
            }

            if (this.newPickItem.product?.has_variants && !this.newPickItem.variant_uuid) {
                return this.notifications.warning('Select a variant for this product.');
            }

            const quantityRequested = Number(this.newPickItem.quantity_requested ?? 0);
            if (!quantityRequested || quantityRequested <= 0) {
                return this.notifications.warning('Enter a requested quantity greater than zero.');
            }

            const item = this.store.createRecord('pick-list-item', {
                pick_list_uuid: this.getRecordUuid(this.newPickItem.pickList),
                product_uuid: this.newPickItem.product_uuid,
                variant_uuid: this.newPickItem.variant_uuid,
                inventory_uuid: this.newPickItem.inventory_uuid,
                bin_location_uuid: this.newPickItem.bin_location_uuid,
                quantity_requested: quantityRequested,
                quantity_picked: 0,
                sequence_number: Number(this.newPickItem.sequence_number ?? 0),
                status: 'pending',
            });
            await item.save();
            this.notifications.success('Pick list item added.');
            this.resetNewPickItem();
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    async updatePickList(pickList, actionName, payload = {}) {
        try {
            await this.fetch.post(`pick-lists/${pickList.public_id ?? pickList.id}/${actionName}`, payload, { namespace: 'pallet/int/v1' });
            this.notifications.success(`Pick list ${this.actionLabel(actionName)} successfully.`);
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action startPickList(pickList) {
        return this.updatePickList(pickList, 'start');
    }

    @action assignPickList(pickList) {
        return this.updatePickList(pickList, 'assign', { assigned_to_uuid: this.currentUserUuid });
    }

    @action completePickList(pickList) {
        return this.updatePickList(pickList, 'complete');
    }

    @action async markPickListItemPicked(item) {
        const quantityPicked = Number(item.quantity_picked ?? item.quantity_requested ?? 0);

        if (!quantityPicked || quantityPicked <= 0) {
            return this.notifications.warning('Enter a picked quantity greater than zero.');
        }

        if (quantityPicked > Number(item.quantity_requested ?? 0)) {
            return this.notifications.warning('Picked quantity cannot exceed requested quantity.');
        }

        try {
            await this.fetch.post(
                `pick-list-items/${item.public_id ?? item.id}/picked`,
                {
                    quantity_picked: quantityPicked,
                    picked_by_uuid: this.currentUserUuid,
                },
                { namespace: 'pallet/int/v1' }
            );
            this.notifications.success('Pick list item marked picked.');
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    actionLabel(actionName) {
        if (actionName === 'start') {
            return 'started';
        }

        if (actionName === 'assign') {
            return 'assigned';
        }

        return `${actionName}d`;
    }
}
