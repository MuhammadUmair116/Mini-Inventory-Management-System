/**
 * Format a decimal amount for display only.
 * All authoritative financial calculations happen on the server.
 */
export function formatCurrency(amount, symbol = 'PKR', locale = 'en-US') {
    const value = Number.parseFloat(String(amount ?? 0));

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: symbol,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(value) ? value : 0);
}
