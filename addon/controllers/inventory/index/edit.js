import Controller from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { task } from 'ember-concurrency';

export default class InventoryIndexEditController extends Controller {
    @service hostRouter;
    @service intl;
    @service notifications;
    @service modalsManager;
    @service events;

    @tracked overlay;

    /**
     * The panel header's action buttons. The template binds this; without it the
     * edit panel renders no way back to the record's details.
     */
    get actionButtons() {
        return [
            {
                icon: 'eye',
                fn: this.view,
            },
        ];
    }

    /**
     * The template bound @saveTask={{this.save}} and @onPressCancel={{this.cancel}}
     * but neither existed here, so the panel rendered no save button at all: you
     * could open the edit form, change anything you liked, and have no way to keep
     * it. Mirrors the warehouse edit controller, which is the working example.
     */
    @task *save(inventory) {
        try {
            yield inventory.save();
            this.events.trackResourceUpdated(inventory);
            this.overlay?.close();

            yield this.hostRouter.transitionTo('console.pallet.inventory.index.details', inventory);
            this.notifications.success(
                this.intl.t('common.resource-updated-success', {
                    resource: 'Inventory',
                    resourceName: inventory.get('product.name') ?? inventory.public_id,
                })
            );
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action cancel() {
        if (this.model.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(this.model, 'console.pallet.inventory.index');
        }

        return this.hostRouter.transitionTo('console.pallet.inventory.index');
    }

    @action view() {
        if (this.model.hasDirtyAttributes) {
            return this.confirmContinueWithUnsavedChanges(this.model, 'console.pallet.inventory.index.details', this.model);
        }

        return this.hostRouter.transitionTo('console.pallet.inventory.index.details', this.model);
    }

    /**
     * The list route has no dynamic segment, so passing the record to it threw
     * "More context objects were passed than there are dynamic segments" — the
     * modal closed, rollbackAttributes() had already discarded the edits, and the
     * transition never ran. Cancel lost your work and left you on the form.
     * Models are passed explicitly now, and only where the route takes one.
     */
    confirmContinueWithUnsavedChanges(inventory, routeName, ...models) {
        return this.modalsManager.confirm({
            title: this.intl.t('common.continue-without-saving'),
            body: this.intl.t('common.continue-without-saving-prompt', { resource: 'Inventory' }),
            acceptButtonText: this.intl.t('common.continue'),
            confirm: async () => {
                inventory.rollbackAttributes();
                await this.hostRouter.transitionTo(routeName, ...models);
            },
        });
    }
}
