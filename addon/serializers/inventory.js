import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { underscore } from '@ember/string';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';
import payloadModelName, { CATALOG_PAYLOAD_MODEL_NAMES } from '../utils/payload-model-names';

export default class InventorySerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    /**
     * Embedded relationship attributes
     *
     * @var {Object}
     */
    get attrs() {
        return {
            product: { embedded: 'always' },
            variant: { embedded: 'always' },
            warehouse: { embedded: 'always' },
            batch: { embedded: 'always' },
            supplier: { embedded: 'always' },
            zone: { embedded: 'always' },
            binLocation: { embedded: 'always' },
        };
    }

    /**
     * The resource emits `bin_location` while the model declares `binLocation`,
     * and ember-core's ApplicationSerializer calls this hook without defining it.
     * Without the bridge the relation never populates.
     */
    keyForRelationship(key) {
        return underscore(key);
    }

    /**
     * The API serves the catalog models unprefixed (`product`, `variant`) while they
     * are registered as `pallet-product` / `pallet-product-variant`. Without this the
     * embedded record resolved to the console's own `product` model, which re-exports
     * from an engine that is not installed — reserving stock succeeded but raised
     * "Could not find module @fleetbase/storefront-engine/models/product" at the user.
     */
    modelNameFromPayloadKey(key) {
        return payloadModelName(CATALOG_PAYLOAD_MODEL_NAMES, key, super.modelNameFromPayloadKey(key));
    }
}
