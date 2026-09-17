import Component from '@glimmer/component';

/**
 * What actually changed in one audit event.
 *
 * SCREENS.md §G's must-never for this screen is showing before and after as a raw JSON
 * blob in the row. A blob is technically complete and practically unreadable: the
 * reader has to diff two objects by eye to find the one field that moved.
 *
 * So this pairs old and new by key and shows **only the keys that differ**. An audit
 * row for a status change should read as one line, not as forty unchanged fields with
 * one buried among them.
 */
export default class AuditDiffComponent extends Component {
    get changes() {
        const before = this.args.resource?.old_values ?? {};
        const after = this.args.resource?.new_values ?? {};
        const keys = [...new Set([...Object.keys(before), ...Object.keys(after)])].sort();

        return keys.map((key) => ({ key, before: this.format(before[key]), after: this.format(after[key]) })).filter((change) => change.before !== change.after);
    }

    /**
     * Metadata is separate from the diff: it is context the event carried, not something
     * that changed. Flattened to one line per entry so it reads as facts rather than as
     * a nested structure.
     */
    get meta() {
        const meta = this.args.resource?.meta;

        if (!meta || typeof meta !== 'object') {
            return [];
        }

        return Object.keys(meta)
            .sort()
            .map((key) => ({ key, value: this.format(meta[key]) }));
    }

    get hasDetail() {
        return this.changes.length > 0 || this.meta.length > 0;
    }

    /**
     * Objects and arrays still have to be shown somehow; they are stringified rather
     * than dumped over several lines, and only ever inside a single field's cell — the
     * must-never is about the whole record arriving as a blob, not about a nested value
     * being unrepresentable.
     */
    format(value) {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        if (typeof value === 'object') {
            return JSON.stringify(value);
        }

        return String(value);
    }
}
