import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | warehouse-editor', function (hooks) {
    setupRenderingTest(hooks);

    // The console layout provides this outlet; the editor's overlay is wormholed into it.
    hooks.beforeEach(function () {
        this.outlet = document.createElement('div');
        this.outlet.id = 'warehouse-overlay-render-outlet';
        document.body.appendChild(this.outlet);
    });

    hooks.afterEach(function () {
        this.outlet.remove();
    });

    test('it renders the editor trigger and wormholes the editor overlay', async function (assert) {
        this.warehouse = this.owner.lookup('service:store').createRecord('warehouse', { public_id: 'warehouse_1', name: 'North DC' });

        await render(hbs`<WarehouseEditor @warehouse={{this.warehouse}} />`);

        assert.dom(this.element).includesText('Warehouse Editor');
        assert.dom('.next-content-overlay', this.outlet).exists();
        assert.dom(this.outlet).includesText('Add Section');
    });
});
