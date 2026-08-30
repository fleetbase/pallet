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

    /**
     * Cancel was wired straight to `transition-to`, so the record
     * createNewInstance() had already put in the store was left behind on every
     * cancelled create. They accumulate for the life of the session — visible to
     * anything reading the store rather than the API, and a fresh orphan each time
     * the panel is reopened. Rolling back removes an unsaved record from the store
     * outright.
     */
    @action cancel() {
        const record = this.binLocation;

        if (record?.isNew) {
            record.rollbackAttributes();
        }

        this.overlay?.close();

        return this.hostRouter.transitionTo('console.pallet.facilities.locations.index');
    }

    @action resetForm() {
        this.binLocation = this.binLocationActions.createNewInstance();
    }
}
