import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class InventoryFormComponent extends Component {
    @tracked statusOptions = [
        { label: 'In Stock', value: 'in_stock' },
        { label: 'Out of Stock', value: 'out_of_stock' },
        { label: 'On Order', value: 'on_order' },
        { label: 'Reserved', value: 'reserved' },
    ];

    @action setExpiryDate(event) {
        if (this.args.resource.batch) {
            this.args.resource.batch.expiryDate = event.target.value;
        }
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    @action setProduct(product) {
        this.args.resource.product = product;
        this.args.resource.product_uuid = this.getRecordUuid(product);
        this.args.resource.variant = null;
        this.args.resource.variant_uuid = null;
    }

    @action setVariant(variant) {
        this.args.resource.variant = variant;
        this.args.resource.variant_uuid = this.getRecordUuid(variant);
    }

    @action setWarehouse(warehouse) {
        this.args.resource.warehouse = warehouse;
        this.args.resource.warehouse_uuid = this.getRecordUuid(warehouse);
    }

    @action setSupplier(supplier) {
        this.args.resource.supplier = supplier;
        this.args.resource.supplier_uuid = this.getRecordUuid(supplier);
    }

    @action setBinLocation(binLocation) {
        this.args.resource.binLocation = binLocation;
        this.args.resource.bin_location_uuid = this.getRecordUuid(binLocation);
    }

    @action setZone(zone) {
        this.args.resource.zone = zone;
        this.args.resource.zone_uuid = this.getRecordUuid(zone);
    }
}
