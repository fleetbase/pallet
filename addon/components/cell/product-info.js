import Component from '@glimmer/component';
import { action, computed, get } from '@ember/object';

export default class CellProductInfoComponent extends Component {
    @computed('args.{row,column.modelPath}') get product() {
        const { column, row } = this.args;

        if (typeof column?.modelPath === 'string') {
            return get(row, column.modelPath);
        }

        return row;
    }

    get categoryName() {
        return this.product?.category?.name ?? this.product?.category?.label ?? this.product?.category;
    }

    get supplierName() {
        return this.product?.supplier?.name;
    }

    get variantCountLabel() {
        const count = Number(this.product?.variant_count ?? this.product?.variants?.length ?? 0);
        return count === 1 ? '1 variant' : `${count} variants`;
    }

    @action onClick(event) {
        const { row, column, onClick } = this.args;

        if (typeof onClick === 'function') {
            onClick(row, event);
        }

        if (typeof column?.action === 'function') {
            column.action(row, event);
        }

        if (typeof column?.onClick === 'function') {
            column.onClick(row, event);
        }
    }
}
