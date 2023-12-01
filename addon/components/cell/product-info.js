import Component from '@glimmer/component';
import { action, computed, get } from '@ember/object';

export default class CellProductInfoComponent extends Component {
    @computed('args.row', 'args.column.modelPath') get product() {
        const { column, row } = this.args;

        if (typeof column?.modelPath === 'string') {
            return get(row, column.modelPath);
        }

        return row;
    }

    @action onClick(event) {
        const { row, column, onClick } = this.args;

        if (typeof onClick === 'function') {
            onClick(row, event);
        }

        if (typeof column?.action === 'function') {
            column.action(row, event);
        }

        if (typeof column?.onClick === 'function') {
            column.onClick(row, event);
        }
    }
}
