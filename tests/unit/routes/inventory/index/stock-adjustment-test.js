import { module, test } from 'qunit';
import { setupTest } from 'dummy/tests/helpers';

module('Unit | Route | inventory/index/stock-adjustment', function (hooks) {
    setupTest(hooks);

    test('it exists', function (assert) {
        let route = this.owner.lookup('route:inventory/index/stock-adjustment');
        assert.ok(route);
    });
});
