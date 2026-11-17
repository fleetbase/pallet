import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | supplier-form-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it titles a new supplier', async function (assert) {
        this.supplier = this.owner.lookup('service:store').createRecord('supplier', {});

        await render(hbs`<SupplierFormPanel @supplier={{this.supplier}} />`);

        assert.dom('.next-content-overlay').exists();
        assert.dom('.next-content-overlay').includesText('Add Supplier');
    });

    test('it titles an existing supplier by name', async function (assert) {
        this.supplier = this.owner.lookup('service:store').createRecord('supplier', { name: 'Acme Supply', public_id: 'supplier_1' });

        await render(hbs`<SupplierFormPanel @supplier={{this.supplier}} />`);

        assert.dom('.next-content-overlay').includesText('Acme Supply');
        assert.dom('.next-content-overlay').doesNotIncludeText('Add Supplier');
    });
});
