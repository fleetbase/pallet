import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';

export default class OperationsReservationsController extends Controller {
    @service fetch;
    @service hostRouter;
    @service notifications;
    @service store;

    @tracked newReservation = { type: 'hard', quantity: 1 };
    @tracked storefrontContext = {};
    @tracked contextReservations = null;

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
