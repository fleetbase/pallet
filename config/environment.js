'use strict';
const { name } = require('../package');

module.exports = function (environment) {
    let ENV = {
        modulePrefix: name,
        environment,

        /**
         * Read by the engine, not by the host: `getOwner` inside a mounted engine
         * resolves the engine's own config, so a placeholder declared only in the
         * console is invisible here. Storefront and FleetOps both declare their own
         * for the same reason.
         */
        defaultValues: {
            placeholderImage: getenv('DEFAULT_PLACEHOLDER_IMAGE', 'https://flb-assets.s3.ap-southeast-1.amazonaws.com/images/fallback-placeholder-2.png'),
        },

        'ember-leaflet': {
            excludeCSS: true,
            excludeJS: true,
            excludeImages: true,
        },
    };

    return ENV;
};

function getenv(variable, defaultValue = null) {
    return process.env[variable] !== undefined ? process.env[variable] : defaultValue;
}
