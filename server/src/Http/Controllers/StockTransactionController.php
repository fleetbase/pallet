<?php

namespace Fleetbase\Pallet\Http\Controllers;

/**
 * StockTransactionController — read-only access to the stock movement ledger.
 *
 * Ledger rows are written by the models themselves whenever stock moves
 * (receive, deduct, reserve, commit, transfer, adjust). They are an immutable
 * record, so this controller exposes querying and reading only — creating or
 * editing a movement by hand would let the ledger disagree with the stock it
 * is supposed to explain.
 */
class StockTransactionController extends PalletResourceController
{
    /**
     * The resource to query.
     *
     * @var string
     */
    public $resource = 'stock-transaction';
}
