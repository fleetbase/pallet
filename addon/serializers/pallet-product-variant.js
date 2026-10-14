import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';
import payloadModelName, { CATALOG_PAYLOAD_MODEL_NAMES } from '../utils/payload-model-names';

export default class PalletProductVariantSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    payloadKeyFromModelName() {
        return 'product_variant';
    }

    /**
     * The outbound key was bridged but the inbound one was not, so
     * `product_variants` resolved to `product-variant` — an unregistered model.
     */
    modelNameFromPayloadKey(key) {
        return payloadModelName(CATALOG_PAYLOAD_MODEL_NAMES, key, super.modelNameFromPayloadKey(key));
    }

    /**
     * Same parent-embedding loop as warehouse-zone: the product serializer embeds
     * `variants`, so embedding `product` back from the variant made product →
     * variants → product recurse until the stack gave out.
     */
    get attrs() {
        return {
            product: { serialize: 'ids', deserialize: 'records' },
        };
    }
}
