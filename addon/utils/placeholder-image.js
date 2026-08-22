import { get } from '@ember/object';

/**
 * The fallback image for a record with no photo.
 *
 * `getOwner` inside a mounted engine resolves the *engine's* config, not the
 * console's, which is why Pallet declares its own defaultValues — see
 * config/environment.js. Kept as a util so the four lists that need it do not
 * each carry a copy of that reasoning.
 *
 * @param {EngineInstance} owner  from getOwner(this)
 * @returns {string|undefined}
 */
export default function placeholderImage(owner) {
    const config = owner?.resolveRegistration?.('config:environment');

    return get(config ?? {}, 'defaultValues.placeholderImage');
}
