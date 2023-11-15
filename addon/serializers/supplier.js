import JSONAPISerializer from '@ember-data/serializer/json-api';

export default class SupplierSerializer extends JSONAPISerializer {
    /**
     * Embedded relationship attributes
     *
     * @var {Object}
     */
    get attrs() {
        return {};
    }
}
