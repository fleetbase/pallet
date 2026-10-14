import { module, test } from 'qunit';
import { setupTest } from 'dummy/tests/helpers';

/**
 * Ember Data resolves each payload key with `singularize(dasherize(key))` and
 * silently DROPS any key that resolves to an unregistered model. Three keys hit
 * that trap and took whole screens down with them:
 *
 *   - `waves` singularizes to `wafe`, so the waves route died outright
 *   - `product_variants` resolved to the unprefixed `product-variant`
 *   - the product serializer prefixed everything, turning the embedded
 *     `supplier` into `pallet-supplier` and `variants` into `pallet-variant`
 *
 * Each case fails silently in the browser, so pin the resolution directly.
 */
const RESOLUTIONS = [
    // serializer,               payload key,         expected model
    ['wave', 'waves', 'wave'],
    ['wave', 'wave', 'wave'],
    ['wave', 'warehouse', 'warehouse'],
    ['wave', 'pick_lists', 'pick-list'],
    ['pallet-product', 'products', 'pallet-product'],
    ['pallet-product', 'variants', 'pallet-product-variant'],
    ['pallet-product', 'product_variants', 'pallet-product-variant'],
    ['pallet-product', 'supplier', 'supplier'],
    ['pallet-product-variant', 'product_variants', 'pallet-product-variant'],
    ['pallet-product-variant', 'product', 'pallet-product'],
];

module('Unit | Serializer | payload key resolution', function (hooks) {
    setupTest(hooks);

    RESOLUTIONS.forEach(function ([serializerName, payloadKey, expectedModelName]) {
        test(`${serializerName} resolves "${payloadKey}" to ${expectedModelName}`, function (assert) {
            const store = this.owner.lookup('service:store');
            const serializer = store.serializerFor(serializerName);

            assert.strictEqual(serializer.modelNameFromPayloadKey(payloadKey), expectedModelName);
        });
    });

    test('every resolved model name is actually registered', function (assert) {
        const store = this.owner.lookup('service:store');

        RESOLUTIONS.forEach(function ([serializerName, payloadKey]) {
            const resolved = store.serializerFor(serializerName).modelNameFromPayloadKey(payloadKey);

            assert.ok(store.modelFor(resolved), `${resolved} (from ${serializerName}/"${payloadKey}") is a registered model`);
        });
    });
});
