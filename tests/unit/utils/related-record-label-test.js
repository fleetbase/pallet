import relatedRecordLabel from 'dummy/utils/related-record-label';
import { module, test } from 'qunit';

module('Unit | Utility | related-record-label', function () {
    const options = { uuidPath: 'product_uuid', relationPath: 'product', missingLabel: 'Product unavailable' };

    test('it returns the related record name when the relation resolves', function (assert) {
        const row = { product_uuid: 'abc', product: { uuid: 'abc', name: 'Steel Shelving Bracket' } };

        assert.strictEqual(relatedRecordLabel(row, options), 'Steel Shelving Bracket');
    });

    test('it says the record is unavailable when the key is set but the relation is gone', function (assert) {
        // the product was soft-deleted after the adjustment was written, so the
        // eager load skips it and the relation resolves to nothing
        const row = { product_uuid: 'abc', product: null };

        assert.strictEqual(relatedRecordLabel(row, options), 'Product unavailable');
    });

    test('it stays empty when the row never referenced a record at all', function (assert) {
        assert.strictEqual(relatedRecordLabel({ product_uuid: null, product: null }, options), null);
    });

    test('it looks through an async relationship proxy rather than trusting it', function (assert) {
        // @belongsTo is async: the proxy is truthy even when its content is null
        const emptyProxy = { content: null };
        const loadedProxy = { content: { uuid: 'abc', name: 'Pallet Wrap' } };

        assert.strictEqual(relatedRecordLabel({ product_uuid: 'abc', product: emptyProxy }, options), 'Product unavailable');
        assert.strictEqual(relatedRecordLabel({ product_uuid: 'abc', product: loadedProxy }, options), 'Pallet Wrap');
    });

    test('it honours a namePath other than name', function (assert) {
        const row = { variant_uuid: 'v1', variant: { uuid: 'v1', display_name: 'Large / Blue' } };

        assert.strictEqual(relatedRecordLabel(row, { uuidPath: 'variant_uuid', relationPath: 'variant', namePath: 'display_name', missingLabel: 'x' }), 'Large / Blue');
    });
});
