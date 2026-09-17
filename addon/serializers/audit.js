import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
import { underscore } from '@ember/string';
import { EmbeddedRecordsMixin } from '@ember-data/serializer/rest';

export default class AuditSerializer extends ApplicationSerializer.extend(EmbeddedRecordsMixin) {
    /**
     * Embedded relationship attributes.
     * Only `performedBy` is embedded — the audit trail is immutable and has
     * no `createdBy` distinction (the performer IS the creator).
     *
     * @var {Object}
     */
    get attrs() {
        return {
            performedBy: { embedded: 'always' },
        };
    }

    /**
     * The resource emits `performed_by` while the model declares `performedBy`, and
     * ember-core's ApplicationSerializer calls this hook without ever defining
     * it. Nothing bridged the two, so the relation never populated.
     */
    keyForRelationship(key) {
        return underscore(key);
    }
}
