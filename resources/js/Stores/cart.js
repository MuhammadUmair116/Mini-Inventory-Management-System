import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { calculateDraftTotals } from '../Utils/calculations';

export const useCartStore = defineStore('cart', () => {
    const items = ref([]);
    const discount = ref('0');
    const taxPercent = ref('0');

    const isEmpty = computed(() => items.value.length === 0);
    const totals = computed(() => calculateDraftTotals(items.value, discount.value, taxPercent.value));

    function addItem(product) {
        const existing = items.value.find((item) => item.product_id === product.id);

        if (existing) {
            existing.quantity = String(Number(existing.quantity) + 1);
            return;
        }

        items.value.push({
            product_id: product.id,
            name: product.name,
            sku: product.sku,
            current_stock: product.current_stock,
            quantity: '1',
            unit_price: product.sale_price,
        });
    }

    function updateItem(productId, field, value) {
        const item = items.value.find((entry) => entry.product_id === productId);

        if (item) {
            item[field] = value;
        }
    }

    function removeItem(productId) {
        items.value = items.value.filter((item) => item.product_id !== productId);
    }

    function clear() {
        items.value = [];
        discount.value = '0';
        taxPercent.value = '0';
    }

    return {
        items,
        discount,
        taxPercent,
        isEmpty,
        totals,
        addItem,
        updateItem,
        removeItem,
        clear,
    };
});
