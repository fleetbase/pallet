import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | batch-form-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it titles a new batch', async function (assert) {
        this.batch = this.owner.lookup('service:store').createRecord('batch', {});

        await render(hbs`<BatchFormPanel @batch={{this.batch}} />`);

        assert.dom('.next-content-overlay').exists();
        assert.dom('.next-content-overlay-panel-title').includesText('Add Batch');
    });

    test('it titles an existing batch by name', async function (assert) {
        this.batch = this.owner.lookup('service:store').createRecord('batch', { name: 'Batch 7', public_id: 'batch_7' });

        await render(hbs`<BatchFormPanel @batch={{this.batch}} />`);

        assert.dom('.next-content-overlay').includesText('Batch 7');
        assert.dom('.next-content-overlay-panel-title').doesNotIncludeText('Add Batch');
    });
});
