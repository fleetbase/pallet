import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';

export default class SalesOrderItemSerializer extends ApplicationSerializer {
    embedded = ['product', 'warehouse', 'inventory'];
}
