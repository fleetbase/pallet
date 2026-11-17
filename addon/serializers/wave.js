import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { underscore } from '@ember/string';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';
import payloadModelName, { WAVE_PAYLOAD_MODEL_NAMES } from '../utils/payload-model-names';

export default class WaveSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    /**
     * `waves` singularizes to `wafe` (the knives → knife rule), so the payload
     * resolved to a model that does not exist, Ember Data dropped it, and the
     * waves route died with "expected an array but it was a single record".
     */
    modelNameFromPayloadKey(key) {
        return payloadModelName(WAVE_PAYLOAD_MODEL_NAMES, key, super.modelNameFromPayloadKey(key));
    }

    get attrs() {
        return {
            warehouse: { embedded: 'always' },
            pickLists: { embedded: 'always' },
        };
    }

    /**
     * The resource emits `pick_lists` while the model declares `pickLists`, and
     * ember-core's ApplicationSerializer calls this hook without ever defining
     * it. Nothing bridged the two, so the relation never populated.
     */
    keyForRelationship(key) {
        return underscore(key);
    }
}
