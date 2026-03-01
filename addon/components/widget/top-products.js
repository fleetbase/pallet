import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetTopProductsComponent extends Component {
    @service fetch;
    @service notifications;

    @tracked products = [];
    @tracked isLoading = true;

    constructor() {
        super(...arguments);
        this.loadTopProducts.perform();
    }

    @task({ restartable: true })
    *loadTopProducts() {
        this.isLoading = true;
        try {
            const data = yield this.fetch.get('pallet/metrics/top-products', { limit: 10 });
            this.products = data.products ?? [];
        } catch (error) {
            this.notifications.serverError(error);
        } finally {
            this.isLoading = false;
        }
    }

    get maxMovement() {
        if (!this.products.length) return 1;
        return Math.max(...this.products.map((p) => p.movement_count ?? 0));
    }

    barWidth(count) {
        if (!this.maxMovement) return '0%';
        return `${Math.round((count / this.maxMovement) * 100)}%`;
    }
}
