import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { underscore } from '@ember/string';

export default class WarehouseBinSerializer extends ApplicationSerializer {
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
}
