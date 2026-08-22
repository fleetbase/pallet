import Controller from '@ember/controller';
import { action, get } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class OperationsPickListsController extends Controller {
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
    @tracked isCreatingPickList = false;
    @tracked isAddingPickItem = false;

    /**
     * The vocabulary PickList::boot() documents and normalises to. The form offered a
     * free-text box, so anything typed there became a type the domain does not
     * recognise — the same fault already fixed on zones and bin locations.
     */
    pickListTypes = ['discrete', 'batch', 'zone', 'wave'];

    @tracked newPickList = { type: 'discrete', priority: 5 };
    @tracked newPickItem = { quantity_requested: 1 };

    /**
     * Like cycle counts, the pick sheet is the working surface — it is where
     * picked quantities are entered — so this screen drives Table directly for
     * @canExpand rather than Layout::Resource::Tabular, whose only block replaces
     * the Table.
     */
    @tracked columns = [
        {
            label: this.intl.t('operations.pick-lists.columns.pick-list'),
            valuePath: 'pick_list_number',
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
            label: this.intl.t('operations.pick-lists.wave'),
            valuePath: 'wave.wave_number',
            cellComponent: 'table/cell/base',
            width: '150px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('operations.pick-lists.columns.assigned-to'),
            valuePath: 'assignedTo.name',
            cellComponent: 'table/cell/base',
            width: '150px',
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
            hidden: true,
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
            label: this.intl.t('operations.pick-lists.columns.progress'),
            valuePath: 'completion_percentage',
            cellComponent: 'cell/count',
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
            ddMenuLabel: this.intl.t('operations.pick-lists.actions-menu'),
            cellClassNames: 'overflow-visible',
            wrapperClass: 'flex items-center justify-end mx-2',
            width: '70px',
            actions: [
                {
                    label: this.intl.t('operations.pick-lists.assign-to-me'),
                    icon: 'user-check',
                    fn: this.assignPickList,
                    isVisible: (pickList) => get(pickList, 'status') === 'pending',
                },
                {
                    label: this.intl.t('operations.common.start'),
                    icon: 'play',
                    fn: this.startPickList,
                    isVisible: (pickList) => ['pending', 'assigned'].includes(get(pickList, 'status')),
                },
                {
                    label: this.intl.t('operations.common.complete'),
                    icon: 'check',
                    fn: this.completePickList,
                    isVisible: (pickList) => get(pickList, 'status') === 'in_progress',
                },
                {
                    label: this.intl.t('operations.pick-lists.no-actions'),
                    disabled: true,
                    isVisible: (pickList) => ['completed', 'cancelled'].includes(get(pickList, 'status')),
                },
            ],
            sortable: false,
            filterable: false,
            resizable: false,
            searchable: false,
        },
    ];

    /**
     * Adding a pick item is a second, different task from creating a pick list,
     * so it gets its own header action rather than a second always-open panel.
     */
    get actionButtons() {
        return [
            {
                type: 'default',
                icon: 'plus',
                text: this.intl.t('operations.pick-lists.add-item'),
                onClick: this.startAddingPickItem,
            },
        ];
    }

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

    @action startCreatingPickList() {
        this.isCreatingPickList = true;
    }

    @action cancelCreatingPickList() {
        this.isCreatingPickList = false;
    }

    @action startAddingPickItem() {
        this.isAddingPickItem = true;
    }

    @action cancelAddingPickItem() {
        this.isAddingPickItem = false;
    }

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

    @action setType(type) {
        this.newPickList = { ...this.newPickList, type };
    }

    @action setPickListWarehouse(warehouse) {
        this.newPickList = {
            ...this.newPickList,
            warehouse,
            warehouse_uuid: this.getRecordUuid(warehouse),
        };
    }

    @action setPickListSalesOrder(salesOrder) {
        this.newPickList = {
            ...this.newPickList,
            salesOrder,
            sales_order_uuid: this.getRecordUuid(salesOrder),
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
                sales_order_uuid: this.newPickList.sales_order_uuid,
                type: this.newPickList.type ?? 'discrete',
                priority,
                notes: this.newPickList.notes,
                status: 'pending',
            });
            await pickList.save();
            this.notifications.success('Pick list created.');
            this.isCreatingPickList = false;
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
            this.isAddingPickItem = false;
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
