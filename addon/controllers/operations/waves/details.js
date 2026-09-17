import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class OperationsWavesDetailsController extends Controller {
    @service hostRouter;
    @service intl;
    @service notifications;
    @service fetch;

    @tracked view = 'details';
    @tracked queryParams = ['view'];
    @tracked isReleasing = false;

    /**
     * SCREENS.md §F names RELEASE as the wave's action. It is absent rather than
     * disabled once the wave has left `pending`: releasing twice is refused by the
     * server, and a button whose only outcome is that error is worse than no button.
     */
    get canRelease() {
        return this.model?.status === 'pending';
    }

    get actionButtons() {
        if (!this.canRelease) {
            return [];
        }

        return [
            {
                icon: 'play',
                type: 'primary',
                text: this.intl.t('operations.waves.release'),
                fn: () => this.releaseWave(this.model),
                disabled: this.isReleasing,
            },
        ];
    }

    /**
     * Releasing generates the pick lists, so the document must reload rather than
     * patch its own status — the PICK LISTS panel below is the point of the screen and
     * would otherwise stay empty until a manual refresh.
     */
    @action async releaseWave(wave) {
        if (this.isReleasing) {
            return;
        }

        this.isReleasing = true;

        try {
            await this.fetch.post(`waves/${wave.public_id ?? wave.id}/release`, {}, { namespace: 'pallet/int/v1' });
            await wave.reload();
            this.notifications.success(this.intl.t('operations.waves.released'));
        } catch (error) {
            this.notifications.serverError(error);
        } finally {
            this.isReleasing = false;
        }
    }

    @action transitionBack() {
        return this.hostRouter.transitionTo('console.pallet.operations.waves.index');
    }
}
