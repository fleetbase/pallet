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

    /**
     * A zone must not embed its own parent. The warehouse serializer embeds `zones`,
     * so embedding `warehouse` back from the zone closed a loop — warehouse → zones
     * → warehouse → zones — and every save that serialized a warehouse holding at
     * least one zone died with "Maximum call stack size exceeded". That covered
     * saving an inventory record, since the inventory serializer embeds warehouse
     * too. The zone's warehouse travels as warehouse_uuid, which is what the
     * controller reads anyway.
     */
    get attrs() {
        return {
            warehouse: { serialize: 'ids', deserialize: 'records' },
        };
    }
}
