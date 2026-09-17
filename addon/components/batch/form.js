import Component from '@glimmer/component';
import { action } from '@ember/object';

export default class BatchFormComponent extends Component {
    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    @action setProduct(product) {
        this.args.resource.product = product;
        this.args.resource.product_uuid = this.getRecordUuid(product);
        this.args.resource.variant = null;
        this.args.resource.variant_uuid = null;
    }
}
