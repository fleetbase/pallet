import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';

export default class LocationsIndexEditController extends Controller {
    @service hostRouter;
    @service intl;
    @service notifications;
    @service modalsManager;
    @service events;

    @tracked overlay;

    get actionButtons() {
        return [
            {
                icon: 'eye',
                fn: this.view,
            },
        ];
    }

    @task *save(binLocation) {
        try {
            yield binLocation.save();
            this.events.trackResourceUpdated(binLocation);
            this.overlay?.close();

            yield this.hostRouter.transitionTo('console.pallet.facilities.locations.index.details', binLocation);
            this.notifications.success(
                this.intl.t('common.resource-updated-success', {
                    resource: 'Bin Location',
                    resourceName: binLocation.bin_number,
                })
            );
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action cancel() {
        if (this.model.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(this.model, 'console.pallet.facilities.locations.index');
        }

        return this.hostRouter.transitionTo('console.pallet.facilities.locations.index');
    }

    @action view() {
        if (this.model.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(this.model, 'console.pallet.facilities.locations.index.details');
        }

        return this.hostRouter.transitionTo('console.pallet.facilities.locations.index.details', this.model);
    }

    confirmContinueWithUnsavedChanges(record, routeName) {
        return this.modalsManager.confirm({
            title: this.intl.t('common.continue-without-saving'),
            body: this.intl.t('common.continue-without-saving-prompt', { resource: 'Bin Location' }),
            acceptButtonText: this.intl.t('common.continue'),
            confirm: async () => {
                record.rollbackAttributes();
                await this.hostRouter.transitionTo(routeName, record);
            },
        });
    }
}
