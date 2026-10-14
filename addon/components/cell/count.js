import Component from '@glimmer/component';

export default class CellCountComponent extends Component {
    /**
     * A genuine zero renders as "0"; only a missing value falls back to a dash.
     */
    get display() {
        const { value } = this.args;

        if (value === null || value === undefined || value === '') {
            return '-';
        }

        const number = Number(value);

        return isNaN(number) ? value : String(number);
    }
}
