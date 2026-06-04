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

    @action
    resetForm() {
        this.warehouse = this.warehouseActions.createNewInstance();
    }
}
