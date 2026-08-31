import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { task } from 'ember-concurrency';

export default class WidgetReorderRiskComponent extends Component {
    @service fetch;

    @tracked products = [];
    @tracked error = null;

    constructor() {
        super(...arguments);
        this.load.perform();
    }

    @task *load() {
        try {
            const data = yield this.fetch.get('metrics/reorder-risk', { limit: 10 }, { namespace: 'pallet/int/v1' });
            this.products = data.products ?? [];
            this.error = null;
        } catch (error) {
            this.error = error?.message ?? 'Unable to load reorder risk';
        }
    }
}
