import Engine from '@ember/engine';
import loadInitializers from 'ember-load-initializers';
import Resolver from 'ember-resolver';
import config from './config/environment';
import services from '@fleetbase/ember-core/exports/services';
import AdminProductCategoryComponent from './components/admin/product-category';

const { modulePrefix } = config;
const externalRoutes = ['console', 'extensions'];

export default class PalletEngine extends Engine {
    modulePrefix = modulePrefix;
    Resolver = Resolver;
    dependencies = {
        services,
        externalRoutes,
    };
    setupExtension = function (app, engine, universe) {
        // register menu item in header
        universe.registerHeaderMenuItem('Pallet', 'console.pallet', { icon: 'pallet', priority: 1 });

        // register admin settings -- create a pallet menu panel with it's own setting options
        universe.registerAdminMenuPanel(
            'Pallet Config',
            [
                {
                    title: 'Product Category',
                    icon: 'eye',
                    component: AdminProductCategoryComponent,
                },
            ],
            {
                slug: 'pallet',
            }
        );
    };
}

loadInitializers(PalletEngine, modulePrefix);
