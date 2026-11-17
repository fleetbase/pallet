// Inside the console, ember-engines hands the engine the host's router as `hostRouter`.
// Engine components rendered directly in the dummy app look it up from the app, so
// alias it to the app's own router service.
export function initialize(owner) {
    if (!owner.hasRegistration('service:host-router')) {
        owner.register('service:host-router', owner.lookup('service:router'), { instantiate: false });
    }
}

export default {
    initialize,
};
