<?php

namespace Fleetbase\Pallet\Http\Filter;

use Fleetbase\Http\Filter\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Base filter for every tenant-owned Pallet resource.
 *
 * Two things this exists to guarantee:
 *
 * 1. A company clause is ALWAYS present. Fleetbase applies `queryForInternal()` /
 *    `queryForPublic()` from `Filter::apply()`, but only when `Http::isInternalRequest()`
 *    or `Http::isPublicRequest()` says so. `isPublicRequest()` is `Str::startsWith($uri, 'v1/')`,
 *    which never matches Pallet's consumable routes because they live under the package
 *    prefix (`pallet/v1/...`). Relying on those heuristics would leave the public API
 *    unscoped, so the clause is applied here unconditionally instead.
 *
 * 2. The column is table-qualified. Several Pallet listings join or alias their base
 *    table (the inventory summary groups by product), and a bare `company_uuid` is
 *    ambiguous in those queries.
 *
 * Scoping is fail-closed: with no company in session the clause matches nothing rather
 * than everything.
 */
abstract class PalletFilter extends Filter
{
    /**
     * Guards against applying the company clause more than once when both this class's
     * apply() and one of the queryFor* hooks run for the same request.
     */
    protected bool $companyScopeApplied = false;

    public function apply(Builder $builder): Builder
    {
        $builder       = parent::apply($builder);
        $this->builder = $builder;

        $this->scopeToCompany();

        return $this->builder;
    }

    public function queryForInternal()
    {
        $this->scopeToCompany();
    }

    public function queryForPublic()
    {
        $this->scopeToCompany();
    }

    /**
     * Restrict the query to the authenticated company.
     */
    protected function scopeToCompany(): void
    {
        if ($this->companyScopeApplied) {
            return;
        }

        $this->companyScopeApplied = true;

        $this->builder->where($this->qualifyCompanyColumn(), $this->session->get('company'));
    }

    /**
     * Table-qualified company column for the model under query.
     */
    protected function qualifyCompanyColumn(): string
    {
        return $this->builder->getModel()->getTable() . '.company_uuid';
    }
}
