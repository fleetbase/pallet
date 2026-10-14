import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';

export default class WarehousesIndexNewController extends Controller {
    @service warehouseActions;
    @service hostRouter;
    @service intl;
    @service notifications;
    @service events;

    @tracked overlay;
    @tracked warehouse = this.warehouseActions.createNewInstance();

    @task *save(warehouse) {
        try {
            yield warehouse.save();
            this.events.trackResourceCreated(warehouse);
            this.overlay?.close();

            yield this.hostRouter.refresh();
            yield this.hostRouter.transitionTo('console.pallet.facilities.warehouses.index.details', warehouse);
            this.notifications.success(
                this.intl.t('common.resource-created-success-name', {
                    resource: 'Warehouse',
                    resourceName: warehouse.name,
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
        const record = this.warehouse;

        if (record?.isNew) {
            record.rollbackAttributes();
        }

        this.overlay?.close();

        return this.hostRouter.transitionTo('console.pallet.facilities.warehouses.index');
    }

    @action
    resetForm() {
        this.warehouse = this.warehouseActions.createNewInstance();
    }
}
