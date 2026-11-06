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
    @attr('string') storefront_product_uuid;
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
    @attr('number') reserved_stock;
    @attr('boolean') is_out_of_stock;
    @attr('raw') inventory_summary;

    /*
     * The API has always sent the eager-loaded category object (Product::$with
     * includes it, and the resource emits it under `category`), but nothing on
     * this model declared it, so Ember Data dropped it from every payload and
     * `category.name` was permanently undefined. The old detail panel hid that
     * behind a hardcoded "Uncategorized". It is `raw` rather than a belongsTo
     * because there is no category model registered in the engine, and it is not
     * in the backend's $fillable, so echoing it back on save is a no-op.
     */
    @attr('raw') category;
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

    get storefrontAvailableQuantity() {
        return this.inventory_summary?.available_quantity ?? this.available_stock ?? 0;
    }

    get storefrontReservedQuantity() {
        return this.inventory_summary?.reserved_quantity ?? this.reserved_stock ?? 0;
    }

    get storefrontTotalQuantity() {
        return this.inventory_summary?.total_quantity ?? this.total_stock ?? 0;
    }

    get storefrontOutOfStock() {
        return this.inventory_summary?.out_of_stock ?? this.is_out_of_stock ?? false;
    }

    get storefrontInventoryStatus() {
        return this.storefrontOutOfStock ? 'out-of-stock' : 'available';
    }

    /**
     * cell/product-traceability rendered these four flags as separate inline
     * spans, which is what made the column two lines tall. They are one fact —
     * how this product has to be handled — so they read as one line, and a
     * product with no special handling says so rather than showing a dash.
     */
    get traceabilitySummary() {
        const flags = [];

        if (this.is_serialized) {
            flags.push('Serialized');
        }

        if (this.is_lot_tracked) {
            flags.push('Lot');
        }

        if (this.is_perishable) {
            flags.push('Perishable');
        }

        if (this.requires_quality_check) {
            flags.push('QC');
        }

        return flags.length ? flags.join(' · ') : 'Standard';
    }

    /**
     * product/details assembled these inline, so a product with nothing measured
     * rendered "0 x 0 x 0 -" and "0 -". Composed here they collapse to null and
     * the detail panel drops the row instead of printing zeroes as if they were
     * recorded measurements.
     */
    get dimensionsSummary() {
        if (!this.length && !this.width && !this.height) {
            return null;
        }

        const unit = this.dimensions_unit ? ` ${this.dimensions_unit}` : '';

        return `${this.length ?? 0} \u00d7 ${this.width ?? 0} \u00d7 ${this.height ?? 0}${unit}`;
    }

    get weightSummary() {
        if (!this.weight) {
            return null;
        }

        return this.weight_unit ? `${this.weight} ${this.weight_unit}` : `${this.weight}`;
    }

    get storefrontLinkStatus() {
        return this.storefront_product_uuid ? 'linked' : 'unlinked';
    }

    get storefrontReorderPoint() {
        return this.inventory_summary?.reorder_point ?? this.reorder_point ?? 0;
    }

    get storefrontReorderQuantity() {
        return this.inventory_summary?.reorder_quantity ?? this.reorder_quantity ?? 0;
    }
}
