import ResourceActionService from '@fleetbase/ember-core/services/resource-action';

export default class WarehouseZoneActionsService extends ResourceActionService {
    constructor() {
        super(...arguments);

        this.initialize('warehouse-zone', {
            modelNamePath: 'name',
            defaultAttributes: {
                type: 'general',
                status: 'active',
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
        view: (zone) => this.transitionTo('facilities.zones.index.details', zone),
        edit: (zone) => this.transitionTo('facilities.zones.index.edit', zone),
        create: () => this.transitionTo('facilities.zones.index.new'),
    };
}
