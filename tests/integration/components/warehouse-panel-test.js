import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | warehouse-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it shows the warehouse with its details tab', async function (assert) {
        this.warehouse = this.owner.lookup('service:store').createRecord('warehouse', { public_id: 'warehouse_1', name: 'North DC', latitude: 1.3521, longitude: 103.8198 });

        await render(hbs`<WarehousePanel @warehouse={{this.warehouse}} />`);

        assert.dom('.next-content-overlay').includesText('North DC');
        assert.dom('.next-content-overlay').includesText('Details');
        assert.dom('.ui-tabs .ui-tab.active').includesText('Details');
    });
});
