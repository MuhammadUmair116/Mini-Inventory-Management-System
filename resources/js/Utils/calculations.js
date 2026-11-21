/**
 * Display-only helpers for invoice drafts.
 * The server recalculates every financial value inside a database transaction.
 */

export function toMinorUnits(value) {
    return Math.round(Number.parseFloat(String(value ?? 0)) * 100);
}

export function fromMinorUnits(value) {
    return (Math.round(Number(value ?? 0)) / 100).toFixed(2);
}

export function calculateDraftTotals(items, discount = 0, taxPercent = 0) {
    const subtotalMinor = items.reduce((sum, item) => {
        return sum + toMinorUnits(item.quantity) * toMinorUnits(item.unit_price) / 100;
    }, 0);

    const discountMinor = Math.min(toMinorUnits(discount), subtotalMinor);
    const taxableMinor = subtotalMinor - discountMinor;
    const taxMinor = Math.round(taxableMinor * Number(taxPercent) / 100);
    const totalMinor = taxableMinor + taxMinor;

    return {
        subtotal: fromMinorUnits(subtotalMinor),
        discount: fromMinorUnits(discountMinor),
        tax: fromMinorUnits(taxMinor),
        total: fromMinorUnits(totalMinor),
    };
}
