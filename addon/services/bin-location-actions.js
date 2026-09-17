import ResourceActionService from '@fleetbase/ember-core/services/resource-action';

export default class BinLocationActionsService extends ResourceActionService {
    constructor() {
        super(...arguments);

        this.initialize('bin-location', {
            modelNamePath: 'bin_number',
            defaultAttributes: {
                type: 'standard',
                status: 'active',
                is_pickable: true,
                is_replenishable: true,
                meta: {},
            },
            fetchOptions: {
                namespace: 'pallet/int/v1',
            },
            permissionPrefix: 'pallet',
            mountPrefix: 'console.pallet',
        });
    }

    transition = {
        view: (location) => this.transitionTo('facilities.locations.index.details', location),
        edit: (location) => this.transitionTo('facilities.locations.index.edit', location),
        create: () => this.transitionTo('facilities.locations.index.new'),
    };
}
