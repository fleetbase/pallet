import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { inject as service } from '@ember/service';

export default class ProductStorefrontInventoryComponent extends Component {
    @service fetch;
    @service notifications;

    @tracked storefrontProductUuid = this.args.product?.storefront_product_uuid;
    @tracked isLoading = false;

    get productId() {
        return this.getRecordUuid(this.args.product);
    }

    get canLink() {
        return Boolean(this.productId && this.storefrontProductUuid);
    }

    get hasStorefrontLink() {
        return Boolean(this.args.product?.storefront_product_uuid);
    }

    get variants() {
        return this.args.product?.variants ?? [];
    }

    get variantLinks() {
        return this.variants
            .filter((variant) => this.getRecordUuid(variant))
            .map((variant) => {
                return {
                    pallet_variant_uuid: this.getRecordUuid(variant),
                    storefront_variant_uuid: variant.storefront_variant_uuid ?? null,
                };
            });
    }

    get canSaveVariantLinks() {
        return Boolean(this.canLink && this.variantLinks.length);
    }

    valueFromPayload(payload, key, fallback) {
        return Object.prototype.hasOwnProperty.call(payload ?? {}, key) ? payload[key] : fallback;
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.public_id ?? record?.id;
    }

    updateProductFromResponse(response = {}) {
        const payload = response.product ?? response;
        const product = this.args.product;

        if (!product || !payload) {
            return;
        }

        product.storefront_product_uuid = this.valueFromPayload(payload, 'storefront_product_uuid', product.storefront_product_uuid);
        product.inventory_summary = this.valueFromPayload(response, 'inventory_summary', this.valueFromPayload(payload, 'inventory_summary', product.inventory_summary));
        product.total_stock = this.valueFromPayload(payload, 'total_stock', product.inventory_summary?.total_quantity ?? product.total_stock);
        product.available_stock = this.valueFromPayload(payload, 'available_stock', product.inventory_summary?.available_quantity ?? product.available_stock);
        product.reserved_stock = this.valueFromPayload(payload, 'reserved_stock', product.inventory_summary?.reserved_quantity ?? product.reserved_stock);
        product.is_out_of_stock = this.valueFromPayload(payload, 'is_out_of_stock', product.inventory_summary?.out_of_stock ?? product.is_out_of_stock);
        this.storefrontProductUuid = product.storefront_product_uuid;
    }

    updateVariantsFromResponse(response = {}) {
        const payload = response.product ?? response;
        const variants = payload.variants ?? [];

        variants.forEach((variantPayload) => {
            const variant = this.variants.find((candidate) => {
                return candidate.uuid === variantPayload.uuid || candidate.id === variantPayload.id || candidate.public_id === variantPayload.public_id;
            });

            if (variant) {
                variant.storefront_variant_uuid = this.valueFromPayload(variantPayload, 'storefront_variant_uuid', variant.storefront_variant_uuid);
                variant.inventory_summary = this.valueFromPayload(variantPayload, 'inventory_summary', variant.inventory_summary);
                variant.total_stock = this.valueFromPayload(variantPayload, 'total_stock', variant.total_stock);
                variant.available_stock = this.valueFromPayload(variantPayload, 'available_stock', variant.available_stock);
                variant.reserved_stock = this.valueFromPayload(variantPayload, 'reserved_stock', variant.reserved_stock);
                variant.is_out_of_stock = this.valueFromPayload(variantPayload, 'is_out_of_stock', variant.is_out_of_stock);
            }
        });
    }

    updateVariantsFromAvailabilityBatch(response = {}) {
        const items = response.items ?? [];

        items.forEach((item) => {
            const variant = this.variants.find((candidate) => {
                return candidate.uuid === item.variant_uuid || candidate.public_id === item.variant_uuid || candidate.storefront_variant_uuid === item.storefront_variant_uuid;
            });

            if (variant) {
                variant.inventory_summary = this.valueFromPayload(item, 'inventory_summary', variant.inventory_summary);
                variant.total_stock = this.valueFromPayload(item, 'total_quantity', variant.total_stock);
                variant.available_stock = this.valueFromPayload(item, 'available_quantity', variant.available_stock);
                variant.reserved_stock = this.valueFromPayload(item, 'reserved_quantity', variant.reserved_stock);
                variant.is_out_of_stock = this.valueFromPayload(item, 'out_of_stock', variant.is_out_of_stock);
            }
        });
    }

    @action setStorefrontProductUuid(event) {
        this.storefrontProductUuid = event.target.value;
    }

    @action setVariantStorefrontUuid(variant, event) {
        variant.storefront_variant_uuid = event.target.value;
    }

    @action async linkStorefrontProduct() {
        if (!this.canLink) {
            return;
        }

        this.isLoading = true;

        try {
            const response = await this.fetch.post(
                'storefront/inventory/link',
                {
                    pallet_product_uuid: this.productId,
                    storefront_product_uuid: this.storefrontProductUuid,
                },
                { namespace: 'pallet/int/v1' }
            );

            this.updateProductFromResponse(response);
            this.updateVariantsFromResponse(response);
            this.notifications.success('Storefront product linked.');
        } catch (error) {
            this.notifications.serverError(error);
        } finally {
            this.isLoading = false;
        }
    }

    @action async unlinkStorefrontProduct() {
        if (!this.productId) {
            return;
        }

        this.isLoading = true;

        try {
            const response = await this.fetch.post(
                'storefront/inventory/unlink',
                {
                    pallet_product_uuid: this.productId,
                },
                { namespace: 'pallet/int/v1' }
            );

            this.updateProductFromResponse(response);
            this.updateVariantsFromResponse(response);
            this.storefrontProductUuid = null;
            this.args.product.storefront_product_uuid = null;
            this.notifications.success('Storefront product unlinked.');
        } catch (error) {
            this.notifications.serverError(error);
        } finally {
            this.isLoading = false;
        }
    }

    @action async refreshAvailability() {
        if (!this.productId) {
            return;
        }

        this.isLoading = true;

        try {
            const response = await this.fetch.get(
                'storefront/inventory/availability',
                {
                    pallet_product_uuid: this.productId,
                },
                { namespace: 'pallet/int/v1' }
            );

            this.updateProductFromResponse({ inventory_summary: response.inventory_summary });

            if (this.variants.length) {
                const batchResponse = await this.fetch.post(
                    'storefront/inventory/availability-batch',
                    {
                        items: this.variants.map((variant) => {
                            return {
                                pallet_product_uuid: this.productId,
                                pallet_variant_uuid: this.getRecordUuid(variant),
                                quantity: 1,
                            };
                        }),
                    },
                    { namespace: 'pallet/int/v1' }
                );

                this.updateVariantsFromAvailabilityBatch(batchResponse);
            }

            this.notifications.success('Storefront availability refreshed.');
        } catch (error) {
            this.notifications.serverError(error);
        } finally {
            this.isLoading = false;
        }
    }

    @action async saveVariantLinks() {
        if (!this.canSaveVariantLinks) {
            return;
        }

        this.isLoading = true;

        try {
            const response = await this.fetch.post(
                'storefront/inventory/link',
                {
                    pallet_product_uuid: this.productId,
                    storefront_product_uuid: this.storefrontProductUuid,
                    variants: this.variantLinks,
                },
                { namespace: 'pallet/int/v1' }
            );

            this.updateProductFromResponse(response);
            this.updateVariantsFromResponse(response);
            this.notifications.success('Storefront variant links saved.');
        } catch (error) {
            this.notifications.serverError(error);
        } finally {
            this.isLoading = false;
        }
    }
}
