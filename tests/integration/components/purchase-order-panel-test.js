import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | purchase-order-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it shows the purchase order with its details tab', async function (assert) {
        this.purchaseOrder = this.owner.lookup('service:store').createRecord('purchase-order', { public_id: 'po_42' });

        await render(hbs`<PurchaseOrderPanel @purchaseOrder={{this.purchaseOrder}} />`);

        assert.dom('.next-content-overlay').includesText('Purchase Order Details');
        assert.dom('.next-content-overlay').includesText('po_42');
        assert.dom('.ui-tabs .ui-tab.active').includesText('Details');
    });
});
