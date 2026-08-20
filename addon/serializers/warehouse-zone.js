import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';

export default class WarehouseZoneSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    /**
     * Ember Data roots the payload at camelize(modelName), so a save posted
     * `{"warehouseZone": {...}}` while the API reads `warehouse_zone`. The
     * controller then saw no warehouse_uuid and rejected every create with
     * "A warehouse is required to create a zone."
     */
    payloadKeyFromModelName() {
        return 'warehouse_zone';
    }

    get attrs() {
        return {
            warehouse: { embedded: 'always' },
        };
    }
}
