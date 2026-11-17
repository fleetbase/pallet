import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | batch-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it shows the batch with its details tab', async function (assert) {
        this.batch = this.owner.lookup('service:store').createRecord('batch', { public_id: 'batch_7', name: 'Batch 7' });

        await render(hbs`<BatchPanel @batch={{this.batch}} />`);

        assert.dom('.next-content-overlay').includesText('Batch Details');
        assert.dom('.next-content-overlay').includesText('batch_7');
        assert.dom('.ui-tabs .ui-tab.active').includesText('Details');
    });
});
