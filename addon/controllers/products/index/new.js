import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';

export default class ProductsIndexNewController extends Controller {
    @service productActions;
    @service hostRouter;
    @service intl;
    @service notifications;
    @service events;

    @tracked overlay;
    @tracked product = this.productActions.createNewInstance();

    @task *save(product) {
        try {
            yield product.save();
            this.events.trackResourceCreated(product);
            this.overlay?.close();

            yield this.hostRouter.refresh();
            yield this.hostRouter.transitionTo('console.pallet.catalog.products.index.details', product);
            this.notifications.success(
                this.intl.t('common.resource-created-success-name', {
                    resource: 'Product',
                    resourceName: product.name,
                })
            );
            this.resetForm();
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action
    resetForm() {
        this.product = this.productActions.createNewInstance();
    }
}
