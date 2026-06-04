import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';

export default class SuppliersIndexNewController extends Controller {
    @service supplierActions;
    @service hostRouter;
    @service intl;
    @service notifications;
    @service events;

    @tracked overlay;
    @tracked supplier = this.supplierActions.createNewInstance();

    @task *save(supplier) {
        try {
            yield supplier.save();
            this.events.trackResourceCreated(supplier);
            this.overlay?.close();

            yield this.hostRouter.refresh();
            yield this.hostRouter.transitionTo('console.pallet.catalog.suppliers.index.details', supplier);
            this.notifications.success(
                this.intl.t('common.resource-created-success-name', {
                    resource: 'Supplier',
                    resourceName: supplier.name,
                })
            );
            this.resetForm();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action
    resetForm() {
        this.supplier = this.supplierActions.createNewInstance();
    }
}
