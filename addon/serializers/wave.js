import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';

export default class WaveSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    get attrs() {
        return {
            warehouse: { embedded: 'always' },
            pickLists: { embedded: 'always' },
        };
    }
}
