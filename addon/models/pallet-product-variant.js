import Model, { attr, belongsTo } from '@ember-data/model';

export default class PalletProductVariantModel extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') created_by_uuid;
    @attr('string') product_uuid;
    @attr('string') storefront_variant_uuid;

    /** @relationships */
    @belongsTo('pallet-product', { async: false }) product;

    /** @attributes */
    @attr('string') name;
    @attr('string') display_name;
    @attr('string') sku;
    @attr('string') barcode;
    @attr('raw') option_values;
    @attr('string') currency;
    @attr('number') unit_cost;
    @attr('number') unit_price;
    @attr('number') sale_price;
    @attr('number') declared_value;
    @attr('number') weight;
    @attr('string') weight_unit;
    @attr('number') total_stock;
    @attr('number') available_stock;
    @attr('number') reserved_stock;
    @attr('boolean') is_out_of_stock;
    @attr('raw') inventory_summary;
    @attr('string') status;
    @attr('raw') meta;
    @attr('date') created_at;
    @attr('date') updated_at;

    get storefrontAvailableQuantity() {
        return this.inventory_summary?.available_quantity ?? this.available_stock ?? 0;
    }

    get storefrontReservedQuantity() {
        return this.inventory_summary?.reserved_quantity ?? this.reserved_stock ?? 0;
    }

    get storefrontOutOfStock() {
        return this.inventory_summary?.out_of_stock ?? this.is_out_of_stock ?? false;
    }

    get optionSummary() {
        const options = this.option_values;

        if (!options) {
            return null;
        }

        if (typeof options === 'string') {
            return options;
        }

        return Object.entries(options)
            .map(([key, value]) => `${key}: ${value}`)
            .join(', ');
    }
}
