/**
 * Payload keys whose Ember model name cannot be derived by inflection alone.
 *
 * Ember Data resolves each payload key with `singularize(dasherize(key))` and
 * silently drops any key that resolves to an unregistered model. Two things
 * break that assumption in Pallet:
 *
 *   - the catalog models are registered under a `pallet-` prefix while the API
 *     serves them unprefixed (`products`, `variants`), and
 *   - `waves` singularizes to `wafe` (the knives → knife rule), which matches
 *     no model at all, so the whole payload is discarded.
 *
 * Mapping the affected keys explicitly keeps every other key — `supplier`,
 * `warehouse`, `pick_lists` — resolving through normal inflection.
 */
export const CATALOG_PAYLOAD_MODEL_NAMES = {
    product: 'pallet-product',
    products: 'pallet-product',
    variant: 'pallet-product-variant',
    variants: 'pallet-product-variant',
    product_variant: 'pallet-product-variant',
    product_variants: 'pallet-product-variant',
};

export const WAVE_PAYLOAD_MODEL_NAMES = {
    wave: 'wave',
    waves: 'wave',
};

export default function payloadModelName(map, key, fallback) {
    return map[key] ?? fallback;
}
