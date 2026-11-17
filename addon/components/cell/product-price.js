import Component from '@glimmer/component';

export default class CellProductPriceComponent extends Component {
    get product() {
        return this.args.row;
    }

    get currency() {
        return this.product?.currency;
    }

    get price() {
        return Number(this.product?.unit_price ?? 0);
    }

    get cost() {
        return Number(this.product?.unit_cost ?? 0);
    }

    get salePrice() {
        return Number(this.product?.sale_price ?? 0);
    }

    get declaredValue() {
        return Number(this.product?.declared_value ?? 0);
    }

    get hasSalePrice() {
        return this.salePrice > 0;
    }
}
