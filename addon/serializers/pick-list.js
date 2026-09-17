import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { underscore } from '@ember/string';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';

export default class PickListSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    /**
     * Ember Data roots a save at camelize(modelName) while the API reads the
     * snake_case key, so a create posts a root the controller cannot see and
     * fails validation with no clue why.
     *
     * Derived from the modelName argument rather than hardcoded: this class is
     * shared by more than one model in some cases, and each needs its own key.
     */
    payloadKeyFromModelName(modelName) {
        return underscore(modelName);
    }

    get attrs() {
        return {
            warehouse: { embedded: 'always' },
            // the wave serializer embeds pickLists; embedding the wave back closes
            // the same loop that broke warehouse-zone and product variants
            wave: { serialize: 'ids', deserialize: 'records' },
            assignedTo: { embedded: 'always' },
            items: { embedded: 'always' },
        };
    }

    /**
     * Every Pallet resource emits its relations snake_case (`from_warehouse`,
     * `assigned_to`, `bin_location`) while the models declare them camelCase, and
     * ember-core's ApplicationSerializer calls this hook without ever defining it.
     * Nothing bridged the two, so these relations never populated: the transfers
     * list showed a dash for both FROM and TO on a transfer that has them.
     */
    keyForRelationship(key) {
        return underscore(key);
    }
}
