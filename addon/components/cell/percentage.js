import Component from '@glimmer/component';

/**
 * A percentage column, with its unit.
 *
 * Three columns — pick list PROGRESS, cycle count ACCURACY and zone utilisation — held
 * a server-computed percentage but rendered through `cell/count`, which prints a bare
 * number. A pick list read "100" and a cycle count read "0", leaving the reader to
 * guess whether that was a count of lines, a score, or a per-cent.
 *
 * This is the warehouse "250%" defect inverted: there a unit count had a per-cent sign
 * appended, here percentages lost theirs. Both come from a column not saying what its
 * number measures.
 *
 * Zero is kept, as in `cell/count` — 0% is a real answer and a falsy-check would turn
 * a pick list that has not started into an unknown one. Trailing zeros are trimmed so
 * the server's round(x, 2) does not surface as "100.00%".
 */
export default class CellPercentageComponent extends Component {
    get display() {
        const { value } = this.args;

        if (value === null || value === undefined || value === '') {
            return '-';
        }

        const number = Number(value);

        if (isNaN(number)) {
            return value;
        }

        return `${parseFloat(number.toFixed(2))}%`;
    }
}
