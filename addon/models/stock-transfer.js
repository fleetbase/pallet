import Model, { attr, belongsTo, hasMany } from '@ember-data/model';

export default class StockTransferModel extends Model {
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') from_warehouse_uuid;
    @attr('string') to_warehouse_uuid;
    @belongsTo('warehouse', { async: false, inverse: null }) fromWarehouse;
    @belongsTo('warehouse', { async: false, inverse: null }) toWarehouse;
    @hasMany('stock-transfer-item', { async: false }) items;
    @attr('string') transfer_number;
    @attr('string') status;
    @attr('string') type;
    @attr('string') requested_by_uuid;
    @attr('string') approved_by_uuid;
    @attr('date') shipped_at;
    @attr('date') received_at;
    @attr('number') total_items;
    @attr('number') total_quantity;
    @attr('string') notes;
    @attr() meta;
    @attr('date') created_at;
    @attr('date') updated_at;
}
