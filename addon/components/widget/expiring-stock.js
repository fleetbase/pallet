import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetExpiringStockComponent extends Component {
    @service fetch;
    @service notifications;

    @tracked items = [];
    @tracked isLoading = true;

    constructor() {
        super(...arguments);
        this.loadExpiringStock.perform();
    }

    @task({ restartable: true })
    *loadExpiringStock() {
        this.isLoading = true;
        try {
            const data = yield this.fetch.get('pallet/metrics/expiring-stock', { days: 30, limit: 10 });
            this.items = data.items ?? [];
        } catch (error) {
            this.notifications.serverError(error);
        } finally {
            this.isLoading = false;
        }
    }

    daysUntilExpiry(expiryDate) {
        if (!expiryDate) return null;
        const now = new Date();
        const expiry = new Date(expiryDate);
        return Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
    }

    expiryBadgeClass(days) {
        if (days <= 7) return 'text-red-600 bg-red-50 dark:bg-red-900/20';
        if (days <= 14) return 'text-orange-600 bg-orange-50 dark:bg-orange-900/20';
        return 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20';
    }
}
