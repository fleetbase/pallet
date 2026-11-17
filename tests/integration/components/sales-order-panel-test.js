import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | sales-order-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it shows the sales order with its details tab', async function (assert) {
        this.salesOrder = this.owner.lookup('service:store').createRecord('sales-order', { public_id: 'so_42' });

        await render(hbs`<SalesOrderPanel @salesOrder={{this.salesOrder}} />`);

        assert.dom('.next-content-overlay').includesText('Sales Order Details');
        assert.dom('.next-content-overlay').includesText('so_42');
        assert.dom('.ui-tabs .ui-tab.active').includesText('Details');
    });
});
