import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { underscore } from '@ember/string';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';

export default class PurchaseOrderSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
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

    /**
     * Embedded relationship attributes
     *
     * @var {Object}
     */
    get attrs() {
        return {
            supplier: { embedded: 'always' },
            items: { embedded: 'always' },
            product: { embedded: 'always' },
            variant: { embedded: 'always' },
            warehouse: { embedded: 'always' },
        };
    }
}
