import Component from '@glimmer/component';

export default class CellProductStorefrontComponent extends Component {
    get product() {
        return this.args.row;
    }

    get isLinked() {
        return Boolean(this.product?.storefront_product_uuid);
    }

    get badgeStatus() {
        return this.isLinked ? 'success' : 'warning';
    }

    get label() {
        return this.isLinked ? 'Linked' : 'Unlinked';
    }

    get shortStorefrontId() {
        const id = this.product?.storefront_product_uuid;
        if (!id || id.length <= 14) {
            return id;
        }

        return `${id.slice(0, 8)}...${id.slice(-4)}`;
    }
}
