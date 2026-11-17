import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | supplier-panel/details', function (hooks) {
    setupRenderingTest(hooks);

    test('it lists the supplier fields', async function (assert) {
        this.supplier = { name: 'Acme Supply', email: 'orders@acme.test', phone: '+6560000000' };

        await render(hbs`<SupplierPanel::Details @supplier={{this.supplier}} />`);

        assert.dom(this.element).includesText('Details');
        assert.dom(this.element).includesText('Acme Supply');
        assert.dom(this.element).includesText('orders@acme.test');
    });
});
