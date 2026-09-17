import Component from '@glimmer/component';
import { inject as service } from '@ember/service';

/**
 * Expiry condition as a badge.
 *
 * Exists because ember-ui's `table/cell/status` is `<Badge @status={{@value}} />` — it
 * can only pass a status slug, and DESIGN_SYSTEM §7.2 needs two of these three to go
 * through `@type` instead. Verified in badge.css: `expired` has rules,
 * `expiring-soon` and `ok` have none, so passing them as a status renders an unstyled
 * badge. §7.1's answer is to name a generic colour and pass the text, which needs a
 * cell that can do both.
 *
 * Deliberately not added for the low-stock list: that screen is pre-filtered, so a
 * badge reading the same thing on every row carries nothing. Here the list is
 * unfiltered and the column is what separates a problem from a non-problem.
 */
export default class CellExpiryStatusComponent extends Component {
    @service intl;

    get badge() {
        switch (this.args.row?.expiryStatus) {
            case 'expired':
                // Direct badge.css key.
                return { status: 'expired', text: this.intl.t('inventory.tracking.expired') };
            case 'expiring_soon':
                return { type: 'orange', text: this.intl.t('inventory.tracking.expiring-soon') };
            case 'ok':
                return { type: 'green', text: this.intl.t('inventory.tracking.in-date') };
            default:
                return null;
        }
    }
}
