/**
 * The statuses an order can actually hold.
 *
 * Every one of the four order forms had invented its own list, and none of them
 * matched the backend. The edit forms offered `draft`, `processing`, `shipped` and
 * `delivered` on a sales order and `draft` and `approved` on a purchase order — none
 * of which any code path ever sets — while omitting `partial` and `fulfilled`, which
 * are the two the fulfilment controller actually writes. The create panels were worse:
 * both carried `['pending', 'active', 'prospective', 'archived']`, copied from some
 * unrelated resource, so a new purchase order could be filed as "prospective".
 *
 * That is not cosmetic. Saving a sales order as `shipped` produces a status nothing
 * recognises: SalesOrderController::fulfill only refuses `fulfilled` and `cancelled`,
 * so a "shipped" order stays fulfillable, and the status has no translation, so the
 * badge renders the raw value.
 *
 * These lists are the statuses the server writes:
 *   - both: `pending` on create (SalesOrderController:87, PurchaseOrderController:73)
 *   - sales: `partial` / `fulfilled` (SalesOrder::markAsPartiallyFulfilled / markAsFulfilled)
 *   - purchase: `partial` / `received` (PurchaseOrder, same pair of methods)
 *   - both: `cancelled`
 *
 * Each value has a matching key under `status:` in the translations, which is what
 * the forms render — the labels were hardcoded English before.
 */
export default function getOrderStatusOptions(type = 'sales') {
    return type === 'purchase' ? ['pending', 'partial', 'received', 'cancelled'] : ['pending', 'partial', 'fulfilled', 'cancelled'];
}
