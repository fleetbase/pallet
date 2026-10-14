import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class InventoryIndexNewController extends Controller {
    /**
     * Inject the `store` service
     *
     * @memberof InventoryIndexNewController
     */
    @service store;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof InventoryIndexNewController
     */
    @service hostRouter;

    /**
     * Inject the `hostRouter` service
     *
     * @memberof InventoryIndexNewController
     */
    @service modalsManager;

    /**
     * The overlay component context.
     *
     * @memberof InventoryIndexNewController
     */
    @tracked overlay;

    /**
     * The product being created.
     *
     * @var {InventoryModel}
     */
    @tracked inventory = this.store.createRecord('inventory', {
        type: 'pallet-inventory',
        batch: this.store.createRecord('batch'),
        meta: {},
    });

    /**
     * Set the overlay component context object.
     *
     * @param {OverlayContext} overlay
     * @memberof InventoryIndexNewController
     */
    @action setOverlayContext(overlay) {
        this.overlay = overlay;
    }

    /**
     * When exiting the overlay.
     *
     * @return {Transition}
     * @memberof InventoryIndexNewController
     */
    /**
     * Cancel left the record createRecord() had already put in the store behind on
     * every abandoned create — a fresh orphan each time the panel is reopened, for the
     * life of the session. Same defect the create panels had; this route reaches the
     * exit through transitionBack rather than an onPressCancel action, which is why it
     * was missed the first time round.
     */
    @action transitionBack() {
        const record = this.inventory;

        if (record?.isNew) {
            // The batch is created alongside the inventory row and orphans with it.
            const batch = record.belongsTo('batch').value();

            if (batch?.isNew) {
                batch.rollbackAttributes();
            }

            record.rollbackAttributes();
        }

        return this.hostRouter.transitionTo('console.pallet.inventory.index');
    }

    /**
     * Trigger a route refresh and focus the new product created.
     *
     * @param {InventoryModel} inventory
     * @return {Promise}
     * @memberof InventoryIndexNewController
     */
    @action onAfterSave(inventory) {
        if (this.overlay) {
            this.overlay.close();
        }

        this.hostRouter.refresh();
        return this.hostRouter.transitionTo('console.pallet.inventory.index.details', inventory).then(() => {
            this.resetForm();
        });
    }

    /**
     * Resets the form with a new Inventory record
     *
     * @memberof InventoryIndexNewController
     */
    resetForm() {
        this.inventory = this.store.createRecord('inventory', {
            type: 'pallet-inventory',
            batch: this.store.createRecord('batch'),
            meta: {},
        });
    }
}
