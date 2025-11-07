import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';

export default class ProductDetailsComponent extends Component {
    @tracked metadataButtons = [
        {
            type: 'default',
            text: 'Edit Metadata',
            icon: 'edit',
            onClick: this.editMetadata,
        },
    ];

    @action editMetadata() {
        // Implement metadata editing logic
    }
}
