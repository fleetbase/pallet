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

    /**
     * Cancel was wired straight to `transition-to`, so the record
     * createNewInstance() had already put in the store was left behind on every
     * cancelled create. They accumulate for the life of the session — visible to
     * anything reading the store rather than the API, and a fresh orphan each time
     * the panel is reopened. Rolling back removes an unsaved record from the store
     * outright.
     */
    @action cancel() {
        const product = this.product;

        if (product?.isNew) {
            product.rollbackAttributes();
        }

        this.overlay?.close();

        return this.hostRouter.transitionTo('console.pallet.catalog.products.index');
    }

    @action
    resetForm() {
        this.product = this.productActions.createNewInstance();
    }
}
