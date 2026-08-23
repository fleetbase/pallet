import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';

export default class ProductsIndexEditController extends Controller {
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

    @task *save(product) {
        try {
            yield product.save();
            this.events.trackResourceUpdated(product);
            this.overlay?.close();

            yield this.hostRouter.transitionTo('console.pallet.catalog.products.index.details', product);
            this.notifications.success(
                this.intl.t('common.resource-updated-success', {
                    resource: 'Product',
                    resourceName: product.name,
                })
            );
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action cancel() {
        if (this.model.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(this.model, 'console.pallet.catalog.products.index');
        }

        return this.hostRouter.transitionTo('console.pallet.catalog.products.index');
    }

    @action view() {
        if (this.model.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(this.model, 'console.pallet.catalog.products.index.details', this.model);
        }

        return this.hostRouter.transitionTo('console.pallet.catalog.products.index.details', this.model);
    }

    /**
     * The list route has no dynamic segment, so passing the record to it threw
     * "More context objects were passed than there are dynamic segments" — the
     * modal closed, rollbackAttributes() had already discarded the edits, and the
     * transition never ran. Cancel lost your work and left you on the form.
     * Models are passed explicitly now, and only where the route takes one.
     */
    confirmContinueWithUnsavedChanges(product, routeName, ...models) {
        return this.modalsManager.confirm({
            title: this.intl.t('common.continue-without-saving'),
            body: this.intl.t('common.continue-without-saving-prompt', { resource: 'Product' }),
            acceptButtonText: this.intl.t('common.continue'),
            confirm: async () => {
                product.rollbackAttributes();
                await this.hostRouter.transitionTo(routeName, ...models);
            },
        });
    }
}
