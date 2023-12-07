import Engine from '@ember/engine';
import loadInitializers from 'ember-load-initializers';
import Resolver from 'ember-resolver';
import config from './config/environment';
import services from '@fleetbase/ember-core/exports/services';
import AdminVisibilityControlsComponent from './components/admin/visibility-controls';

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
        universe.registerHeaderMenuItem('Pallet', 'console.pallet', { icon: 'pallet', priority: 0 });

        // register admin settings -- create a pallet menu panel with it's own setting options
        universe.registerAdminMenuPanel(
            'Pallet Config',
            [
                {
                    title: 'Visibility Controls',
                    icon: 'eye',
                    component: AdminVisibilityControlsComponent,
                },
            ],
            {
                slug: 'pallet',
            }
        );

        // create primary registry for engine
        universe.createRegistry('engine:pallet');

        // register the product panel
        universe.createRegistry('component:product-panel');

        // register the inventory panel
        universe.createRegistry('component:inventory-panel');

        // register the supplier panel
        universe.createRegistry('component:supplier-panel');

        // register the warehouse panel
        universe.createRegistry('component:warehouse-panel');
    };
}

loadInitializers(PalletEngine, modulePrefix);
