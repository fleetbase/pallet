import { module, test } from 'qunit';
import { setupTest } from 'dummy/tests/helpers';

module('Unit | Adapter | expired stock', function (hooks) {
    setupTest(hooks);

    // Replace this with your real tests.
    test('it exists', function (assert) {
        let adapter = this.owner.lookup('adapter:expired-stock');
        assert.ok(adapter);
    });
});
