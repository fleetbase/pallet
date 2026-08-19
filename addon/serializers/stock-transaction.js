import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';

export default class StockTransaction extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    /**
     * Embedded relationship attributes
     *
     * @var {Object}
     */
    get attrs() {
        return {
            product: { embedded: 'always' },
            variant: { embedded: 'always' },
            batch: { embedded: 'always' },
            inventory: { embedded: 'always' },
            warehouse: { embedded: 'always' },
        };
    }
}
