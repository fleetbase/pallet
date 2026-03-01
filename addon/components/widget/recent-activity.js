import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetRecentActivityComponent extends Component {
    @service fetch;
    @service notifications;

    @tracked events = [];
    @tracked isLoading = true;

    constructor() {
        super(...arguments);
        this.loadActivity.perform();
    }

    @task({ restartable: true })
    *loadActivity() {
        this.isLoading = true;
        try {
            const data = yield this.fetch.get('pallet/audits', { limit: 15, sort: '-created_at' });
            this.events = data.audits ?? data ?? [];
        } catch (error) {
            this.notifications.serverError(error);
        } finally {
            this.isLoading = false;
        }
    }

    eventIcon(eventType) {
        const icons = {
            stock_adjustment: 'sliders',
            po_received: 'truck-ramp-box',
            so_fulfilled: 'box-open',
            cycle_count: 'clipboard-list',
            stock_transfer: 'arrows-left-right',
            inventory_created: 'plus-circle',
            batch_created: 'layer-group',
        };
        return icons[eventType] ?? 'circle-dot';
    }

    eventColor(eventType) {
        const colors = {
            stock_adjustment: 'text-orange-500',
            po_received: 'text-blue-500',
            so_fulfilled: 'text-green-500',
            cycle_count: 'text-purple-500',
            stock_transfer: 'text-yellow-500',
            inventory_created: 'text-teal-500',
            batch_created: 'text-indigo-500',
        };
        return colors[eventType] ?? 'text-gray-400';
    }
}
