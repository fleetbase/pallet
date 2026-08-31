<?php

namespace Fleetbase\Pallet\Http\Filter;

class SupplierFilter extends PalletFilter
{
    /**
     * Pallet suppliers share the core `vendors` table with FleetOps vendors, so
     * the list has to be narrowed to Pallet's own records.
     *
     * This previously filtered on `type = 'pallet-supplier'`, a sentinel value
     * nothing ever wrote — so the suppliers list could never return a row. The
     * `type` column is also bound to the user-facing "Supplier Type" selector in
     * the form, so it cannot serve as a module discriminator at all.
     *
     * The public id prefix is the reliable discriminator: Supplier declares
     * `$publicIdType = 'supplier'`, so every Pallet supplier is `supplier_…`
     * while FleetOps vendors are `vendor_…`. SUBSTR keeps this exact (a LIKE
     * pattern would treat the underscore as a single-character wildcard) and
     * portable across MySQL and the SQLite test lane.
     */
    protected function scopeToCompany(): void
    {
        if ($this->companyScopeApplied) {
            return;
        }

        parent::scopeToCompany();

        $this->builder->whereRaw('SUBSTR(public_id, 1, 9) = ?', ['supplier_']);
    }

    public function query(?string $query)
    {
        /*
         * Only the search. The pallet-supplier narrowing used to be repeated here as
         * scopeToPalletSuppliers(); that method became the scopeToCompany() override
         * above and this call site was not updated, so every search on the suppliers
         * list raised "Call to undefined method". apply() now runs scopeToCompany()
         * unconditionally, so there is nothing left for this method to re-apply.
         */
        $this->builder->search($query);
    }
}
