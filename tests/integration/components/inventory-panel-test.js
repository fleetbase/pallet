import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | inventory-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it shows the inventory with its details tab', async function (assert) {
        this.inventory = this.owner.lookup('service:store').createRecord('inventory', { public_id: 'inventory_3' });

        await render(hbs`<InventoryPanel @inventory={{this.inventory}} />`);

        assert.dom('.next-content-overlay').includesText('Inventory Details');
        assert.dom('.next-content-overlay').includesText('inventory_3');
        assert.dom('.ui-tabs .ui-tab.active').includesText('Details');
    });
});
