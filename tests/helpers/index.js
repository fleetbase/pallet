import { setupApplicationTest as upstreamSetupApplicationTest, setupRenderingTest as upstreamSetupRenderingTest, setupTest as upstreamSetupTest } from 'ember-qunit';
import { setupIntl } from 'ember-intl/test-support';
import resetStorages from 'ember-local-storage/test-support/reset-storage';

// ember-local-storage caches its storage proxies at module scope, so without a reset a
// test reads proxies owned by the previous test's (destroyed) container.
function setupStorageReset(hooks) {
    hooks.afterEach(function () {
        window.localStorage.clear();
        resetStorages();
    });
}

// This file exists to provide wrappers around ember-qunit's
// test setup functions. This way, you can easily extend the setup that is
// needed per test type.

function setupApplicationTest(hooks, options) {
    upstreamSetupApplicationTest(hooks, options);
    setupStorageReset(hooks);

    // Additional setup for application tests can be done here.
    //
    // For example, if you need an authenticated session for each
    // application test, you could do:
    //
    // hooks.beforeEach(async function () {
    //   await authenticateSession(); // ember-simple-auth
    // });
    //
    // This is also a good place to call test setup functions coming
    // from other addons:
    //
    // setupIntl(hooks); // ember-intl
    // setupMirage(hooks); // ember-cli-mirage
}

function setupRenderingTest(hooks, options) {
    upstreamSetupRenderingTest(hooks, options);
    setupStorageReset(hooks);

    // ember-intl must have its locale before the first render; otherwise the first `t`
    // sets it mid-render and Glimmer rejects the update.
    setupIntl(hooks, 'en-us');

    // Additional setup for rendering tests can be done here.
}

function setupTest(hooks, options) {
    upstreamSetupTest(hooks, options);
    setupStorageReset(hooks);

    // Additional setup for unit tests can be done here.
}

export { setupApplicationTest, setupRenderingTest, setupTest };
