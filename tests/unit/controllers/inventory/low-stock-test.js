import { module, test } from 'qunit';
import { setupTest } from 'dummy/tests/helpers';

module('Unit | Controller | inventory/low-stock', function (hooks) {
    setupTest(hooks);

    // TODO: Replace this with your real tests.
    test('it exists', function (assert) {
        let controller = this.owner.lookup('controller:inventory/low-stock');
        assert.ok(controller);
    });
});
