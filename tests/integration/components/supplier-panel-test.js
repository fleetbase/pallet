import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | supplier-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it shows the supplier with its details tab', async function (assert) {
        this.supplier = this.owner.lookup('service:store').createRecord('supplier', { public_id: 'supplier_1', name: 'Acme Supply' });

        await render(hbs`<SupplierPanel @supplier={{this.supplier}} />`);

        assert.dom('.next-content-overlay').includesText('Supplier Details');
        assert.dom('.next-content-overlay').includesText('Acme Supply');
        assert.dom('.ui-tabs .ui-tab.active').includesText('Details');
    });
});
