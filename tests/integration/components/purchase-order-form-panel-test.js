import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | purchase-order-form-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it titles a new purchase order', async function (assert) {
        this.purchaseOrder = this.owner.lookup('service:store').createRecord('purchase-order', {});

        await render(hbs`<PurchaseOrderFormPanel @purchaseOrder={{this.purchaseOrder}} />`);

        assert.dom('.next-content-overlay').exists();
        assert.dom('.next-content-overlay').includesText('New Purchase Order');
    });
});
