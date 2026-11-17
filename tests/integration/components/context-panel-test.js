import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render, settled } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | context-panel', function (hooks) {
    setupRenderingTest(hooks);

    test('it renders nothing until a record is focused', async function (assert) {
        await render(hbs`<ContextPanel />`);

        assert.dom('.next-content-overlay').doesNotExist();
    });

    test('it renders the registered panel for the focused record and clears it', async function (assert) {
        const contextPanel = this.owner.lookup('service:context-panel');
        const warehouse = this.owner.lookup('service:store').createRecord('warehouse', { public_id: 'warehouse_1', name: 'North DC', latitude: 1.3521, longitude: 103.8198 });

        await render(hbs`<ContextPanel />`);
        contextPanel.focus(warehouse);
        await settled();

        assert.dom('.next-content-overlay').includesText('North DC');

        contextPanel.clear();
        await settled();

        assert.dom('.next-content-overlay').doesNotExist();
    });
});
