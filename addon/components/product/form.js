import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class ProductFormComponent extends Component {
    @service fetch;
    @service notifications;
    @tracked productCategories = [];
    @tracked statusOptions = [
        { label: 'Active', value: 'active' },
        { label: 'Inactive', value: 'inactive' },
        { label: 'Discontinued', value: 'discontinued' },
    ];

    constructor() {
        super(...arguments);
        this.loadProductCategories();
    }

    @action async loadProductCategories() {
        try {
            const categories = await this.fetch.get('categories', { type: 'product' });
            this.productCategories = categories || [];
        } catch (error) {
            this.notifications.serverError(error);
        }
    }
}
