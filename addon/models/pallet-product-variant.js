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
    @attr('string') status;
    @attr('raw') meta;
    @attr('date') created_at;
    @attr('date') updated_at;
}
