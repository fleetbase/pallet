import { module, test } from 'qunit';
import { setupTest } from 'dummy/tests/helpers';

module('Unit | Controllers | canonical grouped controllers', function (hooks) {
    setupTest(hooks);

    test('canonical grouped controllers resolve', function (assert) {
        [
            'catalog/products/index',
            'catalog/products/index/new',
            'catalog/products/index/details',
            'catalog/products/index/edit',
            'catalog/suppliers/index',
            'catalog/suppliers/index/new',
            'catalog/suppliers/index/details',
            'catalog/suppliers/index/edit',
            'facilities/warehouses/index',
            'facilities/warehouses/index/new',
            'facilities/warehouses/index/details',
            'facilities/warehouses/index/edit',
            'inventory/index',
            'inventory/index/new',
            'inventory/index/details',
            'inventory/index/edit',
            'inventory/index/new-stock-adjustment',
            'inventory/adjustments',
            'inventory/low-stock',
            'inventory/expired-stock',
            'orders/purchase-orders/index',
            'orders/purchase-orders/index/new',
            'orders/purchase-orders/index/details',
            'orders/purchase-orders/index/edit',
            'orders/sales-orders/index',
            'orders/sales-orders/index/new',
            'orders/sales-orders/index/details',
            'orders/sales-orders/index/edit',
            'operations/transfers',
            'operations/cycle-counts',
            'operations/pick-lists',
            'operations/waves',
            'operations/reservations',
            'analytics/reports/index',
            'analytics/reports/index/new',
            'analytics/reports/index/details',
            'analytics/reports/index/edit',
            'analytics/audits/index',
            'analytics/audits/index/details',
        ].forEach((controllerName) => {
            assert.ok(this.owner.lookup(`controller:${controllerName}`), `${controllerName} controller resolves`);
        });
    });
});
