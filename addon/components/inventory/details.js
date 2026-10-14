import Component from '@glimmer/component';
import { inject as service } from '@ember/service';

export default class InventoryDetailsComponent extends Component {
    @service intl;

    /**
     * The expiry badge for the tracking panel header, as a colour key plus its label.
     *
     * DESIGN_SYSTEM §7.2: `expired` is a direct badge.css key, but `expiring_soon` is
     * not — verified, `.expiring-soon-status-badge` has zero rules and would render an
     * unstyled badge. §7.1's mechanism for that case is to name a generic colour and
     * pass the text, which ContentPanel supports through @titleStatusRight plus
     * @titleStatusRightText. Zero CSS added either way.
     */
    get expiryBadge() {
        switch (this.args.resource?.expiryStatus) {
            case 'expired':
                return { type: 'expired', text: this.intl.t('inventory.tracking.expired') };
            case 'expiring_soon':
                return { type: 'orange', text: this.intl.t('inventory.tracking.expiring-soon') };
            default:
                return null;
        }
    }
}
