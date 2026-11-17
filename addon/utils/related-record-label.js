import { get } from '@ember/object';

/**
 * Label a cell whose value comes from a related record, distinguishing "there
 * never was one" from "there was one and it has since been deleted".
 *
 * Operational records outlive the things they point at. A stock adjustment is an
 * immutable audit row: delete the product a month later and the adjustment still
 * happened, but the eager load skips the soft-deleted parent and every cell that
 * reads through the relation falls back to a dash. A row of dashes reads as an
 * incomplete record rather than one whose subject is gone.
 *
 * The relation cannot be truth-tested directly — @belongsTo is async, so it is a
 * proxy that stays truthy when its content is null. Test the resolved record.
 *
 * @param {Model} row
 * @param {object} options
 * @param {string} options.uuidPath      where the foreign key lives, e.g. 'product_uuid'
 * @param {string} options.relationPath  the relation, e.g. 'product'
 * @param {string} [options.namePath]    what to show when it resolves, default 'name'
 * @param {string} options.missingLabel  what to show when the key is set but the record is gone
 * @returns {string|null} null when the row genuinely never referenced anything,
 *                        so the cell falls back to its usual empty treatment
 */
export default function relatedRecordLabel(row, { uuidPath, relationPath, namePath = 'name', missingLabel }) {
    const related = get(row ?? {}, relationPath);
    const resolved = related ? (get(related, 'content') ?? related) : null;

    if (get(resolved ?? {}, 'uuid')) {
        return get(resolved, namePath);
    }

    return get(row ?? {}, uuidPath) ? missingLabel : null;
}
