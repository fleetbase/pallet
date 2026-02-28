import ApplicationSerializer from '@fleetbase/ember-core/serializers/application';

export default class PurchaseOrderItemSerializer extends ApplicationSerializer {
    embedded = ['product', 'warehouse'];
}
