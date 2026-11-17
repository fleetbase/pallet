import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class ProductFormComponent extends Component {
    @service currentUser;
    @service fetch;
    @service store;
    @service notifications;
    @tracked productCategories = [];
    @tracked isAddingVariant = false;
    @tracked editingVariant = null;
    @tracked newVariant = {};
    @tracked uploadQueue = [];
    acceptedFileTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    @tracked statusOptions = [
        { label: 'Active', value: 'active' },
        { label: 'Inactive', value: 'inactive' },
        { label: 'Discontinued', value: 'discontinued' },
    ];

    constructor() {
        super(...arguments);
        this.loadProductCategories();
    }

    @action async loadProductCategories() {
        try {
            const categories = await this.fetch.get('categories', { type: 'product' });
            this.productCategories = categories || [];
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    get variants() {
        return this.args.resource?.variants ?? [];
    }

    get canManageVariants() {
        return Boolean(this.args.resource?.id);
    }

    get canManageMedia() {
        return Boolean(this.args.resource?.id);
    }

    get hasImages() {
        return (this.args.resource?.files?.length ?? 0) > 0;
    }

    getRecordUuid(record) {
        return record?.uuid ?? record?.id;
    }

    /**
     * MoneyInput and UnitInput echo the value they were handed back through their
     * change handler. Four MoneyInputs and three UnitInputs share one `currency`
     * and one `dimensions_unit` here, so with `fn (mut ...)` the second instance
     * wrote the property the first had already read in the same render, and Ember
     * asserted: "You attempted to update `currency` ... but it had already been
     * used previously in the same computation." Writing only on a real change
     * breaks that loop.
     */
    @action setCurrency(currency) {
        if (!this.args.resource || this.args.resource.currency === currency) {
            return;
        }

        this.args.resource.currency = currency;
    }

    @action setDimensionsUnit(unit) {
        if (!this.args.resource || this.args.resource.dimensions_unit === unit) {
            return;
        }

        this.args.resource.dimensions_unit = unit;
    }

    @action setWeightUnit(unit) {
        if (!this.args.resource || this.args.resource.weight_unit === unit) {
            return;
        }

        this.args.resource.weight_unit = unit;
    }

    @action setStatus(option) {
        this.args.resource.status = option?.value ?? option;
    }

    @action setProductCategory(category) {
        this.args.resource.category = category;
        this.args.resource.category_uuid = this.getRecordUuid(category);
    }

    @action setSupplier(supplier) {
        this.args.resource.supplier = supplier;
        this.args.resource.supplier_uuid = this.getRecordUuid(supplier);
    }

    variantPayload(variant = {}) {
        return {
            name: variant.name,
            sku: variant.sku,
            barcode: variant.barcode,
            storefront_variant_uuid: variant.storefront_variant_uuid,
            option_values: this.parseOptionValues(variant.option_values_text ?? variant.option_values),
            currency: this.args.resource?.currency,
            unit_cost: variant.unit_cost,
            unit_price: variant.unit_price,
            sale_price: variant.sale_price,
            status: variant.status ?? 'active',
        };
    }

    parseOptionValues(value) {
        if (!value) {
            return {};
        }

        if (typeof value === 'object') {
            return value;
        }

        return value.split(',').reduce((options, part) => {
            const [key, optionValue] = part
                .split('=')
                .map((item) => item?.trim())
                .filter(Boolean);
            if (key && optionValue) {
                options[key] = optionValue;
            }
            return options;
        }, {});
    }

    formatOptionValues(value) {
        if (!value || typeof value !== 'object') {
            return value;
        }

        return Object.entries(value)
            .map(([key, optionValue]) => `${key}=${optionValue}`)
            .join(', ');
    }

    @action startAddingVariant() {
        this.newVariant = { status: 'active' };
        this.isAddingVariant = true;
        this.args.resource.has_variants = true;
    }

    @action cancelAddingVariant() {
        this.newVariant = {};
        this.isAddingVariant = false;
    }

    @action async addVariant() {
        const product = this.args.resource;
        if (!product?.id) {
            this.notifications.warning('Save the product before adding variants.');
            return;
        }

        try {
            const response = await this.fetch.post(`products/${product.id}/variants`, { product_variant: this.variantPayload(this.newVariant) }, { namespace: 'pallet/int/v1' });
            const record = this.store.push(this.store.normalize('pallet-product-variant', response.product_variant ?? response));
            product.variants.pushObject(record);
            product.has_variants = true;
            this.cancelAddingVariant();
            this.notifications.success('Variant added.');
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action startEditingVariant(variant) {
        this.editingVariant = {
            id: variant.id,
            name: variant.name,
            sku: variant.sku,
            barcode: variant.barcode,
            storefront_variant_uuid: variant.storefront_variant_uuid,
            option_values: variant.option_values,
            option_values_text: this.formatOptionValues(variant.option_values),
            unit_cost: variant.unit_cost,
            unit_price: variant.unit_price,
            sale_price: variant.sale_price,
            status: variant.status,
        };
    }

    @action cancelEditingVariant() {
        this.editingVariant = null;
    }

    @action async saveVariant() {
        const product = this.args.resource;
        if (!product?.id || !this.editingVariant?.id) return;

        try {
            const response = await this.fetch.put(
                `products/${product.id}/variants/${this.editingVariant.id}`,
                { product_variant: this.variantPayload(this.editingVariant) },
                { namespace: 'pallet/int/v1' }
            );
            this.store.push(this.store.normalize('pallet-product-variant', response.product_variant ?? response));
            this.editingVariant = null;
            this.notifications.success('Variant updated.');
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action async removeVariant(variant) {
        const product = this.args.resource;
        if (!product?.id || !variant?.id) return;

        try {
            await this.fetch.delete(`products/${product.id}/variants/${variant.id}`, {}, { namespace: 'pallet/int/v1' });
            product.variants.removeObject(variant);
            this.notifications.success('Variant removed.');
        } catch (error) {
            this.notifications.serverError(error);
        }
    }

    @action queueFile(file) {
        const product = this.args.resource;

        if (!product?.id) {
            return this.notifications.warning('Save the product before uploading images.');
        }

        this.uploadQueue.pushObject(file);
        this.fetch.uploadFile.perform(
            file,
            {
                path: `uploads/${this.currentUser.companyId}/pallet-products/${product.id}`,
                subject_uuid: this.getRecordUuid(product),
                subject_type: 'pallet:product',
                type: 'pallet_product',
            },
            (uploadedFile) => {
                product.files.pushObject(uploadedFile);

                if (!product.photo_uuid) {
                    product.photo_uuid = this.getRecordUuid(uploadedFile);
                    product.photo_url = uploadedFile.url;
                    product.photo = uploadedFile;
                }

                this.uploadQueue.removeObject(file);
            },
            (error) => {
                this.notifications.serverError(error);
                this.uploadQueue.removeObject(file);
            }
        );
    }

    @action setProductPhoto(file) {
        if (file.isNotImage) {
            return this.notifications.warning('Only image files can be used as the product photo.');
        }

        this.args.resource.photo_uuid = this.getRecordUuid(file);
        this.args.resource.photo_url = file.url;
        this.args.resource.photo = file;
        this.notifications.success(`${file.original_filename} was made the product photo.`);
    }

    @action async removeFile(file) {
        try {
            await file.destroyRecord();
            this.args.resource.files.removeObject(file);

            if (this.args.resource.photo_uuid === this.getRecordUuid(file)) {
                this.args.resource.photo_uuid = null;
                this.args.resource.photo_url = null;
                this.args.resource.photo = null;
            }
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
