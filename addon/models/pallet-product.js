import Model, { attr, belongsTo, hasMany } from '@ember-data/model';
import { computed } from '@ember/object';
import { format as formatDate, isValid as isValidDate, formatDistanceToNow } from 'date-fns';

export default class PalletProductModel extends Model {
    /** @ids */
    @attr('string') uuid;
    @attr('string') public_id;
    @attr('string') company_uuid;
    @attr('string') created_by_uuid;
    @attr('string') category_uuid;
    @attr('string') supplier_uuid;
    @attr('string') photo_uuid;

    /** @relationships */
    @belongsTo('supplier') supplier;
    @hasMany('pallet-product-variant', { async: false }) variants;
    @hasMany('file') files;

    /** @attributes */
    @attr('string') internal_id;
    @attr('string') name;
    @attr('string') description;
    @attr('string') sku;
    @attr('string') barcode;
    @attr('string') currency;
    @attr('number') unit_cost;
    @attr('number') unit_price;
    @attr('number') sale_price;
    @attr('number') declared_value;
    @attr('number') weight;
    @attr('string') weight_unit;
    @attr('number') length;
    @attr('number') width;
    @attr('number') height;
    @attr('string') dimensions_unit;
    @attr('raw') dimensions;
    @attr('boolean') has_variants;
    @attr('number') variant_count;
    @attr('boolean') is_serialized;
    @attr('boolean') is_lot_tracked;
    @attr('boolean') is_kit;
    @attr('boolean') is_perishable;
    @attr('boolean') requires_quality_check;
    @attr('number') reorder_point;
    @attr('number') reorder_quantity;
    @attr('number') shelf_life_days;
    @attr('number') total_stock;
    @attr('number') available_stock;
    @attr('string') status;
    @attr('string') slug;
    @attr('string') photo_url;
    @attr('raw') meta;
    @attr('date') created_at;
    @attr('date') updated_at;

    @computed('created_at') get createdAt() {
        if (!isValidDate(this.created_at)) {
            return null;
        }
        return formatDate(this.created_at, 'PPP p');
    }

    @computed('created_at') get createdAtShort() {
        if (!isValidDate(this.created_at)) {
            return null;
        }
        return formatDate(this.created_at, 'PP');
    }

    @computed('created_at') get createdAgo() {
        if (!isValidDate(this.created_at)) {
            return null;
        }
        return formatDistanceToNow(this.created_at);
    }

    @computed('updated_at') get updatedAt() {
        if (!isValidDate(this.updated_at)) {
            return null;
        }
        return formatDate(this.updated_at, 'PPP p');
    }

    @computed('updated_at') get updatedAtShort() {
        if (!isValidDate(this.updated_at)) {
            return null;
        }
        return formatDate(this.updated_at, 'PP');
    }

    @computed('updated_at') get updatedAgo() {
        if (!isValidDate(this.updated_at)) {
            return null;
        }
        return formatDistanceToNow(this.updated_at);
    }
}
