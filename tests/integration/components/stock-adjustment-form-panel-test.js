import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | stock-adjustment-form-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it titles a new stock adjustment', async function (assert) {
        this.stockAdjustment = this.owner.lookup('service:store').createRecord('stock-adjustment', {});

        await render(hbs`<StockAdjustmentFormPanel @stockAdjustment={{this.stockAdjustment}} />`);

        assert.dom('.next-content-overlay').exists();
        assert.dom('.next-content-overlay').includesText('Make Stock Adjustment');
    });
});
