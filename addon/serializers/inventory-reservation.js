import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';

export default class InventoryReservationSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    get attrs() {
        return {
            product: { embedded: 'always' },
            variant: { embedded: 'always' },
            inventory: { embedded: 'always' },
            warehouse: { embedded: 'always' },
        };
    }
}
