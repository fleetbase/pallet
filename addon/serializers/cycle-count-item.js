import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { underscore } from '@ember/string';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';
import payloadModelName, { CATALOG_PAYLOAD_MODEL_NAMES } from '../utils/payload-model-names';

export default class CycleCountItemSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    /**
     * Ember Data roots a save at camelize(modelName) while the API reads the
     * snake_case key, so a create posts a root the controller cannot see and
     * fails validation with no clue why.
     *
     * Derived from the modelName argument rather than hardcoded: this class is
     * shared by more than one model in some cases, and each needs its own key.
     */
    payloadKeyFromModelName(modelName) {
        return underscore(modelName);
    }

    get attrs() {
        return {
            product: { embedded: 'always' },
            variant: { embedded: 'always' },
            inventory: { embedded: 'always' },
            binLocation: { embedded: 'always' },
        };
    }

    /**
     * Every Pallet resource emits its relations snake_case (`from_warehouse`,
     * `assigned_to`, `bin_location`) while the models declare them camelCase, and
     * ember-core's ApplicationSerializer calls this hook without ever defining it.
     * Nothing bridged the two, so these relations never populated: the transfers
     * list showed a dash for both FROM and TO on a transfer that has them.
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
