import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';

export default class SuppliersIndexEditController extends Controller {
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

    @task *save(supplier) {
        try {
            yield supplier.save();
            this.events.trackResourceUpdated(supplier);
            this.overlay?.close();

            yield this.hostRouter.transitionTo('console.pallet.catalog.suppliers.index.details', supplier);
            this.notifications.success(
                this.intl.t('common.resource-updated-success', {
                    resource: 'Supplier',
                    resourceName: supplier.name,
                })
            );
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action cancel() {
        if (this.model.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(this.model, 'console.pallet.catalog.suppliers.index');
        }

        return this.hostRouter.transitionTo('console.pallet.catalog.suppliers.index');
    }

    @action view() {
        if (this.model.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(this.model, 'console.pallet.catalog.suppliers.index.details', this.model);
        }

        return this.hostRouter.transitionTo('console.pallet.catalog.suppliers.index.details', this.model);
    }

    /**
     * The list route has no dynamic segment, so passing the record to it threw
     * "More context objects were passed than there are dynamic segments" — the
     * modal closed, rollbackAttributes() had already discarded the edits, and the
     * transition never ran. Cancel lost your work and left you on the form.
     * Models are passed explicitly now, and only where the route takes one.
     */
    confirmContinueWithUnsavedChanges(supplier, routeName, ...models) {
        return this.modalsManager.confirm({
            title: this.intl.t('common.continue-without-saving'),
            body: this.intl.t('common.continue-without-saving-prompt', { resource: 'Supplier' }),
            acceptButtonText: this.intl.t('common.continue'),
            confirm: async () => {
                supplier.rollbackAttributes();
                await this.hostRouter.transitionTo(routeName, ...models);
            },
        });
    }
}
