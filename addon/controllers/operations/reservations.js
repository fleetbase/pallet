import Controller from '@ember/controller';
import { action, get } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { isBlank } from '@ember/utils';
import { task, timeout } from 'ember-concurrency';

export default class OperationsReservationsController extends Controller {
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

    @tracked newReservation = { type: 'hard', quantity: 1 };
    @tracked storefrontContext = {};
    @tracked contextReservations = null;
    @tracked isCreatingReservation = false;
    @tracked isLookingUpContext = false;

    /**
     * Was a hand-rolled <table> whose Storefront Context cell stacked checkout,
     * cart, line and key on four lines, so every row was four lines tall whether
     * the reservation came from a storefront or not. Those are their own columns
     * now, hidden until someone picks them.
     */
    /**
     * SCREENS.md §F lists these as Reservation ID, Product, Batch, Warehouse, Qty
     * reserved, Sales order, Reserved at, Expires at, Status.
     *
     * The reservation id was absent entirely, and **expires_at was declared but hidden**
     * — the one column that makes a reservation need attention, on the screen whose
     * whole subject is stock held against future demand. Variant and the storefront
     * context move to hidden in exchange: they are available from the column picker for
     * the storefront cases that need them, and keeping them visible pushed the row past
     * the table's width, which is what clipped the action menu.
     *
     * Sales order is deliberately absent. The model carries `sales_order_uuid` but no
     * relation to read a number from, and a column headed "Sales Order" showing a bare
     * uuid is worse than no column. It needs the relation before it is worth adding.
     */
    @tracked columns = [
        {
            label: this.intl.t('operations.reservations.columns.reservation'),
            valuePath: 'public_id',
            cellComponent: 'click-to-copy',
            width: '140px',
            resizable: true,
            sortable: true,
            // Hidden by default, and this is a trade rather than an oversight. The table
            // sizes columns to their content, so the nine columns §F lists come to
            // 1234px against a 1033px table and the last two fall off the right edge —
            // which are Status and the action menu, the only interactive things on the
            // row. A reservation's public_id is an internal identifier nobody scans a
            // list by; losing it off-screen costs nothing, losing the actions costs the
            // screen its purpose. It stays one click away in the column picker.
            hidden: true,
        },
        {
            label: this.intl.t('operations.common.product'),
            valuePath: 'product.name',
            cellComponent: 'table/cell/base',
            width: '160px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('operations.reservations.columns.batch'),
            valuePath: 'inventory.batch_number',
            cellComponent: 'table/cell/base',
            width: '100px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('operations.common.warehouse'),
            valuePath: 'warehouse.name',
            cellComponent: 'table/cell/base',
            width: '140px',
            resizable: true,
            sortable: false,
        },
        {
            label: this.intl.t('operations.common.quantity'),
            valuePath: 'quantity',
            cellComponent: 'cell/count',
            width: '80px',
            resizable: true,
            sortable: true,
        },
        {
            label: this.intl.t('operations.reservations.columns.reserved-at'),
            valuePath: 'reservedAt',
            cellComponent: 'table/cell/base',
            width: '120px',
            resizable: true,
            sortable: true,
        },
        {
            label: this.intl.t('operations.reservations.expires'),
            valuePath: 'expiresAt',
            cellComponent: 'table/cell/base',
            width: '120px',
            resizable: true,
            sortable: true,
        },
        {
            label: this.intl.t('common.status'),
            valuePath: 'status',
            cellComponent: 'table/cell/status',
            width: '110px',
            resizable: true,
            sortable: true,
        },
        {
            label: this.intl.t('operations.common.variant'),
            valuePath: 'variant.display_name',
            cellComponent: 'table/cell/base',
            width: '150px',
            resizable: true,
            sortable: false,
            hidden: true,
        },
        {
            label: this.intl.t('operations.reservations.columns.context'),
            valuePath: 'storefrontContextLabel',
            cellComponent: 'click-to-copy',
            width: '160px',
            resizable: true,
            sortable: false,
            hidden: true,
        },
        {
            label: this.intl.t('operations.reservations.checkout'),
            valuePath: 'storefront_checkout_uuid',
            cellComponent: 'click-to-copy',
            width: '160px',
            resizable: true,
            sortable: false,
            hidden: true,
        },
        {
            label: this.intl.t('operations.reservations.cart'),
            valuePath: 'storefront_cart_uuid',
            cellComponent: 'click-to-copy',
            width: '160px',
            resizable: true,
            sortable: false,
            hidden: true,
        },
        {
            label: this.intl.t('operations.reservations.line'),
            valuePath: 'storefront_line_uuid',
            cellComponent: 'click-to-copy',
            width: '160px',
            resizable: true,
            sortable: false,
            hidden: true,
        },
        {
            label: '',
            cellComponent: 'table/cell/dropdown',
            ddButtonText: false,
            ddButtonIcon: 'ellipsis-h',
            ddButtonIconPrefix: 'fas',
            ddMenuLabel: this.intl.t('operations.reservations.actions-menu'),
            cellClassNames: 'overflow-visible',
            wrapperClass: 'flex items-center justify-end mx-2',
            width: '70px',
            actions: [
                {
                    label: this.intl.t('operations.reservations.release'),
                    icon: 'unlock',
                    fn: this.releaseReservation,
                    isVisible: (reservation) => get(reservation, 'is_active'),
                },
                {
                    label: this.intl.t('operations.reservations.fulfill'),
                    icon: 'check',
                    fn: this.fulfillReservation,
                    isVisible: (reservation) => get(reservation, 'is_active'),
                },
                {
                    label: this.intl.t('operations.reservations.no-actions'),
                    disabled: true,
                    isVisible: (reservation) => !get(reservation, 'is_active'),
                },
            ],
            sortable: false,
            filterable: false,
            resizable: false,
            searchable: false,
        },
    ];

    /**
     * The storefront lookup is a support tool, not a warehouse task — it belongs
     * behind a header action rather than as a second always-open panel competing
     * with the list for the top of the screen.
     */
    get actionButtons() {
        return [
            {
                type: 'default',
                icon: 'magnifying-glass',
                text: this.intl.t('operations.reservations.lookup'),
                onClick: this.startContextLookup,
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

    @action startCreatingReservation() {
        this.isCreatingReservation = true;
    }

    @action cancelCreatingReservation() {
        this.isCreatingReservation = false;
    }

    @action startContextLookup() {
        this.isLookingUpContext = true;
    }

    @action closeContextLookup() {
        this.isLookingUpContext = false;
    }

    get reservations() {
        return this.contextReservations ?? this.model;
    }

    get hasContextReservations() {
        return Array.isArray(this.contextReservations);
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    @action setReservationWarehouse(warehouse) {
        this.newReservation = {
            ...this.newReservation,
            warehouse,
            warehouse_uuid: this.getRecordUuid(warehouse),
        };
    }

    resetNewReservation() {
        this.newReservation = { type: 'hard', quantity: 1 };
    }

    @action setReservationProduct(product) {
        this.newReservation = {
            ...this.newReservation,
            product,
            product_uuid: this.getRecordUuid(product),
            variant: null,
            variant_uuid: null,
        };
    }

    @action setReservationVariant(variant) {
        this.newReservation = {
            ...this.newReservation,
            variant,
            variant_uuid: this.getRecordUuid(variant),
        };
    }

    @action async createReservation() {
        try {
            if (!this.newReservation.product_uuid) {
                return this.notifications.warning('Select a product to reserve.');
            }

            if (this.newReservation.product?.has_variants && !this.newReservation.variant_uuid) {
                return this.notifications.warning('Select a variant for this product.');
            }

            if (!this.newReservation.warehouse_uuid) {
                return this.notifications.warning('Select a warehouse for this reservation.');
            }

            const quantity = Number(this.newReservation.quantity ?? 0);
            if (!quantity || quantity <= 0) {
                return this.notifications.warning('Enter a reservation quantity greater than zero.');
            }

            const reservation = this.store.createRecord('inventory-reservation', {
                product_uuid: this.newReservation.product_uuid,
                variant_uuid: this.newReservation.variant_uuid,
                warehouse_uuid: this.newReservation.warehouse_uuid,
                quantity,
                type: this.newReservation.type ?? 'hard',
                expires_at: this.newReservation.expires_at,
            });
            await reservation.save();
            this.notifications.success('Reservation created and stock reserved.');
            this.isCreatingReservation = false;
            this.resetNewReservation();
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    async updateReservation(reservation, actionName) {
        try {
            await this.fetch.post(`inventory-reservations/${reservation.public_id ?? reservation.id}/${actionName}`, {}, { namespace: 'pallet/int/v1' });
            this.notifications.success(`Reservation ${actionName === 'release' ? 'released' : 'fulfilled'} successfully.`);
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action releaseReservation(reservation) {
        return this.updateReservation(reservation, 'release');
    }

    @action fulfillReservation(reservation) {
        return this.updateReservation(reservation, 'fulfill');
    }

    storefrontContextPayload() {
        return Object.fromEntries(Object.entries(this.storefrontContext).filter(([, value]) => Boolean(value)));
    }

    normalizeReservationCollection(response) {
        const reservations = response?.inventory_reservations ?? response?.data ?? response ?? [];
        const collection = Array.isArray(reservations) ? reservations : [reservations];

        return collection.filter(Boolean).map((reservation) => {
            return this.store.push(this.store.normalize('inventory-reservation', reservation.inventory_reservation ?? reservation));
        });
    }

    async fetchStorefrontContextReservations() {
        const payload = this.storefrontContextPayload();

        if (Object.keys(payload).length === 0) {
            this.notifications.warning('Enter a Storefront checkout, cart, order, line, or reservation key.');
            return null;
        }

        try {
            const response = await this.fetch.get('storefront/inventory/reservations/context', payload, { namespace: 'pallet/int/v1' });
            this.contextReservations = this.normalizeReservationCollection(response);
            this.notifications.success(`${this.contextReservations.length} Storefront reservation${this.contextReservations.length === 1 ? '' : 's'} found.`);
            return this.contextReservations;
        } catch (error) {
            this.notifications.serverError(error);
            return null;
        }
    }

    async updateStorefrontContext(actionName) {
        const payload = this.storefrontContextPayload();

        if (Object.keys(payload).length === 0) {
            this.notifications.warning('Enter a Storefront checkout, cart, order, line, or reservation key.');
            return;
        }

        try {
            await this.fetch.post(`storefront/inventory/reservations/${actionName}-context`, payload, { namespace: 'pallet/int/v1' });
            this.notifications.success(`Storefront reservations ${actionName === 'release' ? 'released' : 'committed'} successfully.`);
            if (this.hasContextReservations) {
                await this.fetchStorefrontContextReservations();
            }
            this.hostRouter.refresh();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action releaseStorefrontContext() {
        return this.updateStorefrontContext('release');
    }

    @action fulfillStorefrontContext() {
        return this.updateStorefrontContext('commit');
    }

    @action lookupStorefrontContext() {
        return this.fetchStorefrontContextReservations();
    }

    @action clearStorefrontContextResults() {
        this.contextReservations = null;
    }
}
