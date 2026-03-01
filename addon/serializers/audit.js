import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';
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
}
