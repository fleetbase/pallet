import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { isArray } from '@ember/array';

export default class AdminVisibilityControlsComponent extends Component {
    @service fetch;
    @tracked visibilitySettings = [
        // { name: 'Dashboard', route: 'operations.orders', visible: true },
        { name: 'Products', route: 'products', visible: true },
        { name: 'Warehouses', route: 'warehouses', visible: true },
        { name: 'Suppliers', route: 'suppliers', visible: true },
        { name: 'Inventory', route: 'inventory', visible: true },
        { name: 'Sales Orders', route: 'sales-orders', visible: true },
        { name: 'Purchase Orders', route: 'purchase-orders', visible: true },
        { name: 'Audits', route: 'audits', visible: true },
        { name: 'Reports', route: 'reports', visible: true },
    ];
    @tracked isLoading = false;

    constructor() {
        super(...arguments);
        this.loadVisibilitySettings();
    }

    @action mutateVisibility(route, visible) {
        this.visibilitySettings = [...this.visibilitySettings].map((visibilityControl) => {
            if (visibilityControl.route === route) {
                return {
                    ...visibilityControl,
                    visible,
                };
            }

            return visibilityControl;
        });
    }

    @action loadVisibilitySettings() {
        this.isLoading = true;

        this.fetch
            .get('pallet/settings/visibility')
            .then(({ visibilitySettings }) => {
                if (isArray(visibilitySettings)) {
                    for (let i = 0; i < visibilitySettings.length; i++) {
                        const visibilityControl = visibilitySettings.objectAt(i);
                        this.mutateVisibility(visibilityControl.route, visibilityControl.visible);
                    }
                }
            })
            .catch((error) => {
                this.notifications.serverError(error);
            })
            .finally(() => {
                this.isLoading = false;
            });
    }

    @action saveVisibilitySettings() {
        this.isLoading = true;

        this.fetch
            .post('pallet/settings/visibility', { visibilitySettings: this.visibilitySettings })
            .then(({ visibilitySettings }) => {
                if (isArray(visibilitySettings)) {
                    for (let i = 0; i < visibilitySettings.length; i++) {
                        const visibilityControl = visibilitySettings.objectAt(i);
                        this.mutateVisibility(visibilityControl.route, visibilityControl.visible);
                    }
                }
            })
            .catch((error) => {
                this.notifications.serverError(error);
            })
            .finally(() => {
                this.isLoading = false;
            });
    }
}
