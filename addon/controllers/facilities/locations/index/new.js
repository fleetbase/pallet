import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';

export default class LocationsIndexNewController extends Controller {
    @service binLocationActions;
    @service hostRouter;
    @service intl;
    @service notifications;
    @service events;

    @tracked overlay;
    @tracked binLocation = this.binLocationActions.createNewInstance();

    @task *save(binLocation) {
        try {
            yield binLocation.save();
            this.events.trackResourceCreated(binLocation);
            this.overlay?.close();

            yield this.hostRouter.refresh();
            yield this.hostRouter.transitionTo('console.pallet.facilities.locations.index.details', binLocation);
            this.notifications.success(
                this.intl.t('common.resource-created-success-name', {
                    resource: 'Bin Location',
                    resourceName: binLocation.bin_number,
                })
            );
            this.resetForm();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action resetForm() {
        this.binLocation = this.binLocationActions.createNewInstance();
    }
}
