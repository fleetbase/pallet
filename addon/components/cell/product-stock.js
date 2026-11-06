import Component from '@glimmer/component';

export default class CellProductStockComponent extends Component {
    get product() {
        return this.args.row;
    }

    get available() {
        return Number(this.product?.storefrontAvailableQuantity ?? this.product?.available_stock ?? 0);
    }

    get reserved() {
        return Number(this.product?.storefrontReservedQuantity ?? this.product?.reserved_stock ?? 0);
    }

    get total() {
        return Number(this.product?.storefrontTotalQuantity ?? this.product?.total_stock ?? 0);
    }

    get reorderPoint() {
        return Number(this.product?.storefrontReorderPoint ?? this.product?.reorder_point ?? 0);
    }

    get isOutOfStock() {
        return this.product?.storefrontOutOfStock ?? this.product?.is_out_of_stock ?? this.available <= 0;
    }

    get isLowStock() {
        return !this.isOutOfStock && this.reorderPoint > 0 && this.available <= this.reorderPoint;
    }

    get stockBadgeStatus() {
        if (this.isOutOfStock) {
            return 'danger';
        }

        if (this.isLowStock) {
            return 'warning';
        }

        return 'success';
    }

    get stockLabel() {
        if (this.isOutOfStock) {
            return 'Out of stock';
        }

        if (this.isLowStock) {
            return 'Low stock';
        }

        return 'Available';
    }
}
