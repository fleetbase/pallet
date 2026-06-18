import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';

export default class WarehousesIndexEditController extends Controller {
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

    @task *save(warehouse) {
        try {
            yield warehouse.save();
            this.events.trackResourceUpdated(warehouse);
            this.overlay?.close();

            yield this.hostRouter.transitionTo('console.pallet.facilities.warehouses.index.details', warehouse);
            this.notifications.success(
                this.intl.t('common.resource-updated-success', {
                    resource: 'Warehouse',
                    resourceName: warehouse.name,
                })
            );
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action cancel() {
        if (this.model.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(this.model, 'console.pallet.facilities.warehouses.index');
        }

        return this.hostRouter.transitionTo('console.pallet.facilities.warehouses.index');
    }

    @action view() {
        if (this.model.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(this.model, 'console.pallet.facilities.warehouses.index.details');
        }

        return this.hostRouter.transitionTo('console.pallet.facilities.warehouses.index.details', this.model);
    }

    confirmContinueWithUnsavedChanges(warehouse, routeName) {
        return this.modalsManager.confirm({
            title: this.intl.t('common.continue-without-saving'),
            body: this.intl.t('common.continue-without-saving-prompt', { resource: 'Warehouse' }),
            acceptButtonText: this.intl.t('common.continue'),
            confirm: async () => {
                warehouse.rollbackAttributes();
                await this.hostRouter.transitionTo(routeName, warehouse);
            },
        });
    }
}
