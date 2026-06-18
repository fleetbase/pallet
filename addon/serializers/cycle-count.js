import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';

export default class CycleCountSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    get attrs() {
        return {
            warehouse: { embedded: 'always' },
            zone: { embedded: 'always' },
            assignedTo: { embedded: 'always' },
            items: { embedded: 'always' },
        };
    }
}
