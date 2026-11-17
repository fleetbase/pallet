'use strict';
const { buildEngine } = require('ember-engines/lib/engine-addon');
const { name } = require('./package');

module.exports = buildEngine({
    name,

    // Lazy loading keeps the engine out of the host bundle, but it also keeps the
    // engine's modules out of the dummy app that `ember test` builds, so every test
    // importing `@fleetbase/pallet-engine/*` fails to load. Eager loading is scoped
    // to `ember test` run from this package, so host apps consuming the engine keep
    // lazy loading. Addon index files are evaluated before ember-cli assigns
    // EMBER_ENV, hence the argv check.
    lazyLoading: {
        enabled: !(process.argv.includes('test') && process.cwd() === __dirname),
    },

    isDevelopingAddon() {
        return true;
    },
});
