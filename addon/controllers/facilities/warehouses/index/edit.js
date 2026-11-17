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
            return this.confirmContinueWithUnsavedChanges(this.model, 'console.pallet.facilities.warehouses.index.details', this.model);
        }

        return this.hostRouter.transitionTo('console.pallet.facilities.warehouses.index.details', this.model);
    }

    /**
     * The list route has no dynamic segment, so passing the record to it threw
     * "More context objects were passed than there are dynamic segments" — the
     * modal closed, rollbackAttributes() had already discarded the edits, and the
     * transition never ran. Cancel lost your work and left you on the form.
     * Models are passed explicitly now, and only where the route takes one.
     */
    confirmContinueWithUnsavedChanges(warehouse, routeName, ...models) {
        return this.modalsManager.confirm({
            title: this.intl.t('common.continue-without-saving'),
            body: this.intl.t('common.continue-without-saving-prompt', { resource: 'Warehouse' }),
            acceptButtonText: this.intl.t('common.continue'),
            confirm: async () => {
                warehouse.rollbackAttributes();
                /*
                 * The modal tears itself down as the transition starts, and that
                 * teardown aborts the transition Ember has already begun. The
                 * navigation still completes — measured landing on the list — but
                 * awaiting it here turned the abort into an unhandled rejection and
                 * an uncaught TransitionAborted in the console on every cancel.
                 * Swallow only that; a real routing failure still throws.
                 */
                try {
                    await this.hostRouter.transitionTo(routeName, ...models);
                } catch (error) {
                    if (error?.name !== 'TransitionAborted') {
                        throw error;
                    }
                }
            },
        });
    }
}
