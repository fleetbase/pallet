import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetTopProductsComponent extends Component {
    @service fetch;
    @service notifications;

    @tracked products = [];
    @tracked error = null;

    constructor() {
        super(...arguments);
        this.loadTopProducts.perform();
    }

    @task({ restartable: true })
    *loadTopProducts() {
        try {
            const data = yield this.fetch.get('metrics/top-products', { limit: 10 }, { namespace: 'pallet/int/v1' });
            this.products = data.products ?? [];
            this.error = null;
        } catch (error) {
            this.error = error?.message ?? 'Unable to load top movers';
            this.notifications.serverError(error);
        }
    }

    get maxMovement() {
        if (!this.products.length) return 1;
        return Math.max(...this.products.map((p) => p.movement_count ?? 0));
    }

    get productBars() {
        return this.products.map((product) => {
            const filledCount = this.segmentCount(product.movement_count ?? 0);

            return {
                ...product,
                filledSegments: Array.from({ length: filledCount }),
                emptySegments: Array.from({ length: 10 - filledCount }),
            };
        });
    }

    segmentCount(count) {
        if (!this.maxMovement) return 0;

        return Math.max(0, Math.min(10, Math.round((count / this.maxMovement) * 10)));
    }
}
