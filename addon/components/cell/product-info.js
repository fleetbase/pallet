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

    /**
     * `product` may be a relationship proxy (when the column resolves a
     * belongsTo via modelPath), and Ember asserts on direct property access
     * against a proxy — which threw mid-render and truncated the whole row.
     * Read through Ember's get() so both plain models and proxies work.
     */
    get categoryName() {
        const category = this.readProduct('category');

        if (typeof category === 'string') {
            return category;
        }

        return get(category ?? {}, 'name') ?? get(category ?? {}, 'label') ?? null;
    }

    get supplierName() {
        return this.readProduct('supplier.name');
    }

    get variantCountLabel() {
        const count = Number(this.readProduct('variant_count') ?? this.readProduct('variants.length') ?? 0);

        return count === 1 ? '1 variant' : `${count} variants`;
    }

    readProduct(path) {
        const product = this.product;

        if (!product) {
            return null;
        }

        return get(product, path);
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
