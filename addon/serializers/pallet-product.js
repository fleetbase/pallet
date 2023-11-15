import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';

export default class PalletProductSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    /**
     * Embedded relationship attributes
     *
     * @var {Object}
     */
    get attrs() {
        return {};
    }

    modelNameFromPayloadKey(key) {
        let modelName = super.modelNameFromPayloadKey(key);

        if (!modelName.startsWith('pallet-')) {
            modelName = 'pallet-' + modelName;
        }

        return modelName;
    }

    payloadKeyFromModelName() {
        return 'product';
    }
}
