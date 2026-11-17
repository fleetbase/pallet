import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | warehouse-form-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it titles a new warehouse', async function (assert) {
        this.warehouse = this.owner.lookup('service:store').createRecord('warehouse', {});

        await render(hbs`<WarehouseFormPanel @warehouse={{this.warehouse}} />`);

        assert.dom('.next-content-overlay').exists();
        assert.dom('.next-content-overlay').includesText('New Warehouse');
    });

    test('it titles an existing warehouse by name', async function (assert) {
        this.warehouse = this.owner.lookup('service:store').createRecord('warehouse', { name: 'North DC', public_id: 'warehouse_1' });

        await render(hbs`<WarehouseFormPanel @warehouse={{this.warehouse}} />`);

        assert.dom('.next-content-overlay').includesText('North DC');
        assert.dom('.next-content-overlay').doesNotIncludeText('New Warehouse');
    });
});
