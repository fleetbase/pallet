import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';
import payloadModelName, { CATALOG_PAYLOAD_MODEL_NAMES } from '../utils/payload-model-names';

export default class PalletProductSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    /**
     * Prefixing every key turned the embedded `supplier` into `pallet-supplier`
     * and `variants` into `pallet-variant`, neither of which is a registered
     * model — so both relationships were dropped from every product payload.
     */
    modelNameFromPayloadKey(key) {
        return payloadModelName(CATALOG_PAYLOAD_MODEL_NAMES, key, super.modelNameFromPayloadKey(key));
    }

    payloadKeyFromModelName() {
        return 'product';
    }

    /**
     * Embedded relationship attributes
     *
     * @var {Object}
     */
    get attrs() {
        return {
            supplier: { embedded: 'always' },
            variants: { embedded: 'always' },
        };
    }
}
