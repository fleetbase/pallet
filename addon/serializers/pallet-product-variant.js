import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';

export default class PalletProductVariantSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    payloadKeyFromModelName() {
        return 'product_variant';
    }

    get attrs() {
        return {
            product: { embedded: 'always' },
        };
    }
}
