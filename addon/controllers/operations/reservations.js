import Controller from '@ember/controller';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class OperationsReservationsController extends Controller {
    @service fetch;
    @service hostRouter;
    @service notifications;

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
}
