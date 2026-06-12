import Model, { attr, belongsTo } from '@ember-data/model';

export default class StockTransferItemModel extends Model {
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') stock_transfer_uuid;
    @attr('string') product_uuid;
    @attr('string') variant_uuid;
    @belongsTo('pallet-product', { async: false }) product;
    @belongsTo('pallet-product-variant', { async: false }) variant;
    @attr('number') quantity;
    @attr('number') quantity_received;
    @attr('string') lot_number;
    @attr('string') serial_number;
    @attr('string') notes;
    @attr() meta;
    @attr('date') created_at;
    @attr('date') updated_at;
}
