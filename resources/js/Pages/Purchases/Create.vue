<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2 } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormInput from '../../Components/FormInput.vue';
import FormSelect from '../../Components/FormSelect.vue';
import FormTextarea from '../../Components/FormTextarea.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { useCurrency } from '../../Composables/useCurrency';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    supplierOptions: {
        type: Array,
        default: () => [],
    },
    productOptions: {
        type: Array,
        default: () => [],
    },
});

const { money } = useCurrency();

const today = ref(new Date().toISOString().slice(0, 10));

const form = useForm({
    supplier_id: '',
    invoice_number: '',
    purchase_date: today.value,
    notes: '',
    items: [{ product_id: '', quantity: '1.000', unit_price: '' }],
});

const subtotal = computed(() =>
    form.items.reduce((total, item) => {
        const quantity = Number(item.quantity) || 0;
        const unitPrice = Number(item.unit_price) || 0;

        return total + quantity * unitPrice;
    }, 0),
);

function productFor(item) {
    return props.productOptions.find((option) => String(option.value) === String(item.product_id));
}

function selectProduct(index, value) {
    form.items[index].product_id = value;
    form.items[index].unit_price = productFor(form.items[index])?.price ?? '';
}

function addItem() {
    form.items.push({ product_id: '', quantity: '1.000', unit_price: '' });
}

function removeItem(index) {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
}

function submit() {
    form.post(route('purchases.store'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <div>
        <PageHeader
            title="Record purchase"
            description="Add a supplier delivery and update product stock in one atomic operation."
        >
            <template #actions>
                <Link
                    :href="route('purchases.index')"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    <ArrowLeft :size="16" />
                    Back to purchases
                </Link>
            </template>
        </PageHeader>

        <form class="mt-6 space-y-6" @submit.prevent="submit">
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900">Purchase details</h3>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <FormSelect
                        v-model="form.supplier_id"
                        label="Supplier"
                        :options="supplierOptions"
                        placeholder="Select a supplier"
                        :error="form.errors.supplier_id"
                        required
                    />
                    <FormInput
                        v-model="form.invoice_number"
                        label="Invoice number"
                        placeholder="PUR-2026-0001"
                        :error="form.errors.invoice_number"
                        required
                    />
                    <FormInput
                        v-model="form.purchase_date"
                        label="Purchase date"
                        type="date"
                        :max="today"
                        :error="form.errors.purchase_date"
                        required
                    />
                    <div class="sm:col-span-2 xl:col-span-3">
                        <FormTextarea
                            v-model="form.notes"
                            label="Notes"
                            placeholder="Optional delivery notes"
                            :error="form.errors.notes"
                        />
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Line items</h3>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Amounts shown here are previews; the server recalculates the final totals.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100"
                        @click="addItem"
                    >
                        <Plus :size="16" />
                        Add item
                    </button>
                </div>

                <p v-if="form.errors.items" class="px-6 pt-4 text-xs text-rose-600">
                    {{ form.errors.items }}
                </p>

                <div class="divide-y divide-gray-100">
                    <div
                        v-for="(item, index) in form.items"
                        :key="index"
                        class="grid gap-4 p-6 sm:grid-cols-2 xl:grid-cols-12"
                    >
                        <div class="xl:col-span-5">
                            <FormSelect
                                :model-value="item.product_id"
                                label="Product"
                                :options="productOptions"
                                placeholder="Select a product"
                                :error="form.errors[`items.${index}.product_id`]"
                                required
                                @update:model-value="(value) => selectProduct(index, value)"
                            />
                            <p v-if="productFor(item)" class="mt-1 text-xs text-gray-400">
                                Unit: {{ productFor(item).unit ?? '—' }}
                            </p>
                        </div>
                        <div class="xl:col-span-2">
                            <FormInput
                                v-model="item.quantity"
                                label="Quantity"
                                type="number"
                                step="0.001"
                                min="0.001"
                                :error="form.errors[`items.${index}.quantity`]"
                                required
                            />
                        </div>
                        <div class="xl:col-span-2">
                            <FormInput
                                v-model="item.unit_price"
                                label="Unit price"
                                type="number"
                                step="0.01"
                                min="0"
                                :error="form.errors[`items.${index}.unit_price`]"
                                required
                            />
                        </div>
                        <div class="xl:col-span-2">
                            <span class="mb-1.5 block text-sm font-medium text-gray-700">Line total</span>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                {{ money((Number(item.quantity) || 0) * (Number(item.unit_price) || 0)) }}
                            </div>
                        </div>
                        <div class="flex items-end xl:col-span-1">
                            <button
                                type="button"
                                class="rounded-lg p-2 text-gray-500 hover:bg-rose-50 hover:text-rose-700 disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="form.items.length === 1"
                                :aria-label="`Remove item ${index + 1}`"
                                @click="removeItem(index)"
                            >
                                <Trash2 :size="16" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50/70 px-6 py-4">
                    <span class="text-sm text-gray-500">Displayed subtotal</span>
                    <span class="text-lg font-semibold text-gray-900">{{ money(subtotal) }}</span>
                </div>
            </section>

            <div class="flex justify-end gap-2">
                <Link
                    :href="route('purchases.index')"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </Link>
                <button
                    type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Recording…' : 'Record purchase' }}
                </button>
            </div>
        </form>
    </div>
</template>
