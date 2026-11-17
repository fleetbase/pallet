import { module, test } from 'qunit';
import { setupRenderingTest } from 'dummy/tests/helpers';
import { render } from '@ember/test-helpers';
import { hbs } from 'ember-cli-htmlbars';

module('Integration | Component | admin/product-category', function (hooks) {
    setupRenderingTest(hooks);

    hooks.beforeEach(function () {
        this.queries = [];
        const store = this.owner.lookup('service:store');
        store.query = async (modelName, params) => {
            this.queries.push({ modelName, params });
            const parent = { id: 'cat_1', name: 'Pallets', parent: null };
            const child = { id: 'cat_2', name: 'Euro pallets', parent };
            return [parent, child];
        };
    });

    test('it loads product categories and lists the top-level ones', async function (assert) {
        await render(hbs`<Admin::ProductCategory />`);

        assert.deepEqual(this.queries, [{ modelName: 'category', params: { for: 'pallet_product', with_subcategories: true } }]);
        assert.dom(this.element).includesText('Categories');
        assert.dom(this.element).includesText('Pallets');
        assert.dom(this.element).includesText('Add category');
    });
});
