import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';

export default class StockTransferSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    get attrs() {
        return {
            fromWarehouse: { embedded: 'always' },
            toWarehouse: { embedded: 'always' },
            items: { embedded: 'always' },
        };
    }
}
