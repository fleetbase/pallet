import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class InventoryFormComponent extends Component {
    @service store;
    @service notifications;

    @tracked statusOptions = [
        { label: 'In Stock', value: 'in_stock' },
        { label: 'Out of Stock', value: 'out_of_stock' },
        { label: 'On Order', value: 'on_order' },
        { label: 'Reserved', value: 'reserved' },
    ];

    /**
     * `expiryDate` is not an attribute on the batch model — only `expiry_date_at`
     * is — so this wrote to a property Ember Data does not serialize and the expiry
     * was silently discarded on save. Every batch and every inventory row came back
     * with a null expiry, which is why the batches list showed an empty EXPIRY
     * column and the Expired Stock view could never match anything.
     *
     * Reading it back had the same fault, so the field also came up blank when
     * editing stock that already had an expiry recorded — the inventory model
     * already exposes an `expiryDate` computed in the yyyy-MM-dd a date input
     * wants, which is what the template now binds.
     */
    @action setExpiryDate(event) {
        const { value } = event.target;
        const date = value ? new Date(value) : null;

        // the expired-stock views check the inventory's own expiry
        this.args.resource.expiry_date_at = date;

        // and the batches list reads the batch's, so both have to be written
        if (this.args.resource.batch) {
            this.args.resource.batch.expiry_date_at = date;
        }
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    /**
     * Choosing a product fills in that product's supplier, which is right nearly
     * every time and is one fewer lookup for the user. Only the create overlay did
     * this; editing stock left the supplier stale against the new product.
     */
    @action setProduct(product) {
        this.args.resource.product = product;
        this.args.resource.product_uuid = this.getRecordUuid(product);
        this.args.resource.variant = null;
        this.args.resource.variant_uuid = null;

        // most products have no supplier; findRecord(null) throws
        const supplierUuid = product?.supplier_uuid;

        if (!supplierUuid) {
            this.args.resource.supplier = null;
            this.args.resource.supplier_uuid = null;

            return;
        }

        this.store
            .findRecord('supplier', supplierUuid)
            .then((supplier) => {
                this.args.resource.supplier = supplier;
                this.args.resource.supplier_uuid = this.getRecordUuid(supplier);
            })
            .catch((error) => {
                this.notifications.serverError(error);
            });
    }

    @action setVariant(variant) {
        this.args.resource.variant = variant;
        this.args.resource.variant_uuid = this.getRecordUuid(variant);
    }

    /**
     * Zones and bins belong to a warehouse, so keeping the old ones after the
     * warehouse changes would file the stock in a bin that is somewhere else.
     */
    @action setWarehouse(warehouse) {
        this.args.resource.warehouse = warehouse;
        this.args.resource.warehouse_uuid = this.getRecordUuid(warehouse);
        this.args.resource.zone = null;
        this.args.resource.zone_uuid = null;
        this.args.resource.binLocation = null;
        this.args.resource.bin_location_uuid = null;
    }

    @action setSupplier(supplier) {
        this.args.resource.supplier = supplier;
        this.args.resource.supplier_uuid = this.getRecordUuid(supplier);
    }

    @action setBinLocation(binLocation) {
        this.args.resource.binLocation = binLocation;
        this.args.resource.bin_location_uuid = this.getRecordUuid(binLocation);

        // a bin already knows its zone — no reason to make the user restate it
        this.args.resource.zone_uuid = binLocation?.zone_uuid ?? this.args.resource.zone_uuid;
    }

    @action setZone(zone) {
        this.args.resource.zone = zone;
        this.args.resource.zone_uuid = this.getRecordUuid(zone);
    }
}
