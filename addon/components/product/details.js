import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class ProductDetailsComponent extends Component {
    @tracked metadataButtons = [
        {
            type: 'default',
            text: 'Edit Metadata',
            icon: 'edit',
            onClick: this.editMetadata,
        },
    ];

    @action editMetadata() {
        // Implement metadata editing logic
    }

    get product() {
        return this.args.resource;
    }

    get hasImages() {
        return (this.product?.files?.length ?? 0) > 0;
    }

    get hasInventory() {
        return Number(this.product?.total_stock ?? 0) > 0 || Number(this.product?.reserved_stock ?? 0) > 0;
    }

    get hasVariants() {
        return (this.product?.variants?.length ?? 0) > 0;
    }

    get stockStatus() {
        if (this.product?.is_out_of_stock) {
            return 'danger';
        }

        if (this.isLowStock) {
            return 'warning';
        }

        return 'success';
    }

    get stockStatusLabel() {
        if (this.product?.is_out_of_stock) {
            return 'Out of stock';
        }

        if (this.isLowStock) {
            return 'Low stock';
        }

        return 'Available';
    }

    get isLowStock() {
        const reorderPoint = Number(this.product?.reorder_point ?? 0);
        return reorderPoint > 0 && this.availableStock <= reorderPoint;
    }

    get storefrontLinked() {
        return Boolean(this.product?.storefront_product_uuid);
    }

    get variantCount() {
        return Number(this.product?.variant_count ?? this.product?.variants?.length ?? 0);
    }

    get availableStock() {
        return Number(this.product?.available_stock ?? 0);
    }

    get reservedStock() {
        return Number(this.product?.reserved_stock ?? 0);
    }

    get totalStock() {
        return Number(this.product?.total_stock ?? 0);
    }

    get reorderPoint() {
        return Number(this.product?.reorder_point ?? 0);
    }
}
