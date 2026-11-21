import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatCurrency } from '../Utils/formatCurrency';

export function useCurrency() {
    const page = usePage();

    const symbol = computed(() => page.props.settings?.currency_symbol ?? 'PKR');
    const locale = computed(() => page.props.settings?.locale ?? 'en-US');

    const money = (value) => formatCurrency(value, symbol.value, locale.value);

    return { money, symbol, locale };
}
