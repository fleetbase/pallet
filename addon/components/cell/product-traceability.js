import Component from '@glimmer/component';

export default class CellProductTraceabilityComponent extends Component {
    get product() {
        return this.args.row;
    }

    get hasFlags() {
        return Boolean(this.product?.is_serialized || this.product?.is_lot_tracked || this.product?.is_perishable || this.product?.requires_quality_check);
    }
}
