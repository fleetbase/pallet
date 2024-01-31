import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | admin/product-category', function (hooks) {
    setupRenderingTest(hooks);

    test('it renders', async function (assert) {
        // Set any properties with this.set('myProperty', 'value');
        // Handle any actions with this.set('myAction', function(val) { ... });

        await render(hbs`<Admin::ProductCategory />`);

        assert.dom(this.element).hasText('');

        // Template block usage:
        await render(hbs`
      <Admin::ProductCategory>
        template block text
      </Admin::ProductCategory>
    `);

        assert.dom(this.element).hasText('template block text');
    });
});
