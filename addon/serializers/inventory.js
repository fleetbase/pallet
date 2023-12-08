import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';

export default class InventorySerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    /**
     * Embedded relationship attributes
     *
     * @var {Object}
     */
    get attrs() {
        return {
            product: { embedded: 'always' },
            warehouse: { embedded: 'always' },
            batch: { embedded: 'always' },
            supplier: { embedded: 'always' },
        };
    }
}
