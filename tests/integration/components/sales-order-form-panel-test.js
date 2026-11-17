import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | sales-order-form-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it titles a new sales order', async function (assert) {
        this.salesOrder = this.owner.lookup('service:store').createRecord('sales-order', {});

        await render(hbs`<SalesOrderFormPanel @salesOrder={{this.salesOrder}} />`);

        assert.dom('.next-content-overlay').exists();
        assert.dom('.next-content-overlay').includesText('New Sales Order');
    });
});
