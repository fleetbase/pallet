import Component from '@glimmer/component';
import relatedRecordLabel from '../../utils/related-record-label';

export default class CellRelatedRecordComponent extends Component {
    get resolved() {
        const { column, row } = this.args;

        if (typeof column?.labelValue === 'function') {
            return column.labelValue(row, column);
        }

        return relatedRecordLabel(row, {
            uuidPath: column?.uuidPath,
            relationPath: column?.relationPath,
            namePath: column?.namePath,
            missingLabel: column?.missingLabel,
        });
    }

    /**
     * The row referenced something that is no longer there — as opposed to never
     * having referenced anything, which is an ordinary empty cell.
     */
    get isMissing() {
        const { column } = this.args;

        return Boolean(column?.missingLabel) && this.resolved === column.missingLabel;
    }

    get label() {
        return this.resolved ?? '-';
    }
}
