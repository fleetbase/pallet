<?php

namespace Fleetbase\Pallet\Http\Filter;

use Fleetbase\Http\Filter\Filter;

class SupplierFilter extends Filter
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
    private function scopeToPalletSuppliers(): void
    {
        $this->builder->whereRaw('SUBSTR(public_id, 1, 9) = ?', ['supplier_']);
    }

    public function queryForInternal()
    {
        $this->builder->where('company_uuid', $this->session->get('company'));
        $this->scopeToPalletSuppliers();
    }

    public function queryForPublic()
    {
        $this->builder->where('company_uuid', $this->session->get('company'));
        $this->scopeToPalletSuppliers();
    }

    public function query(?string $query)
    {
        $this->builder->search($query);
        $this->scopeToPalletSuppliers();
    }
}
