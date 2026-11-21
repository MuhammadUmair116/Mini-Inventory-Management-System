<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Building2, Globe2, Save } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import FormInput from '../../Components/FormInput.vue';
import FormSelect from '../../Components/FormSelect.vue';
import FormTextarea from '../../Components/FormTextarea.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { formatCurrency } from '../../Utils/formatCurrency';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    options: {
        type: Object,
        default: () => ({ currencies: [], locales: [] }),
    },
});

const form = useForm({
    app_name: props.settings.app_name ?? '',
    company_name: props.settings.company_name ?? '',
    contact_email: props.settings.contact_email ?? '',
    contact_phone: props.settings.contact_phone ?? '',
    address: props.settings.address ?? '',
    currency_symbol: props.settings.currency_symbol ?? 'PKR',
    locale: props.settings.locale ?? 'en-US',
});

const currencyPreview = computed(() =>
    formatCurrency('1250.50', form.currency_symbol || 'PKR', form.locale || 'en-US'),
);

function submit() {
    form.put(route('settings.update'), { preserveScroll: true });
}
</script>

<template>
    <div>
        <PageHeader
            title="Settings"
            description="Application-wide details and display preferences used across the workspace."
        />

        <div class="grid gap-6 xl:grid-cols-3">
            <form class="space-y-6 xl:col-span-2" @submit.prevent="submit">
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                            <Building2 :size="18" />
                        </span>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Workspace identity</h3>
                            <p class="mt-0.5 text-xs text-gray-400">
                                Shown in navigation and printed reports.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <FormInput
                            v-model="form.app_name"
                            label="Application name"
                            placeholder="Mini Inventory"
                            hint="Displayed beside the logo in the sidebar."
                            :error="form.errors.app_name"
                            required
                        />
                        <FormInput
                            v-model="form.company_name"
                            label="Company name"
                            placeholder="Inventory Manager"
                            :error="form.errors.company_name"
                            required
                        />
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-900">Contact details</h3>
                    <p class="mt-0.5 text-xs text-gray-400">
                        Optional details kept with the application settings.
                    </p>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <FormInput
                            v-model="form.contact_email"
                            label="Contact email"
                            type="email"
                            placeholder="operations@example.com"
                            :error="form.errors.contact_email"
                        />
                        <FormInput
                            v-model="form.contact_phone"
                            label="Contact phone"
                            placeholder="+92 300 0000000"
                            :error="form.errors.contact_phone"
                        />
                        <div class="sm:col-span-2">
                            <FormTextarea
                                v-model="form.address"
                                label="Address"
                                placeholder="Business address"
                                :error="form.errors.address"
                            />
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <Globe2 :size="18" />
                        </span>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Display preferences</h3>
                            <p class="mt-0.5 text-xs text-gray-400">
                                Currency formatting is display-only; calculations remain exact on the server.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <FormSelect
                            v-model="form.currency_symbol"
                            label="Currency"
                            :options="props.options.currencies"
                            :error="form.errors.currency_symbol"
                            required
                        />
                        <FormSelect
                            v-model="form.locale"
                            label="Number locale"
                            :options="props.options.locales"
                            :error="form.errors.locale"
                            required
                        />
                    </div>
                </section>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        <Save :size="16" />
                        {{ form.processing ? 'Saving…' : 'Save settings' }}
                    </button>
                </div>
            </form>

            <aside class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-900">Live preview</h3>
                <p class="mt-0.5 text-xs text-gray-400">Changes are reflected after saving.</p>

                <div class="mt-5 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-5">
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white">
                            <Building2 :size="18" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">
                                {{ form.app_name || 'Mini Inventory' }}
                            </p>
                            <p class="truncate text-xs text-gray-500">
                                {{ form.company_name || 'Inventory Manager' }}
                            </p>
                        </div>
                    </div>

                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-400">Currency</dt>
                            <dd class="font-medium text-gray-800">{{ form.currency_symbol }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-400">Example amount</dt>
                            <dd class="font-medium text-gray-800">{{ currencyPreview }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-400">Locale</dt>
                            <dd class="font-medium text-gray-800">{{ form.locale }}</dd>
                        </div>
                        <div v-if="form.contact_email" class="flex items-center justify-between gap-3">
                            <dt class="text-gray-400">Email</dt>
                            <dd class="truncate font-medium text-gray-800">{{ form.contact_email }}</dd>
                        </div>
                        <div v-if="form.contact_phone" class="flex items-center justify-between gap-3">
                            <dt class="text-gray-400">Phone</dt>
                            <dd class="font-medium text-gray-800">{{ form.contact_phone }}</dd>
                        </div>
                        <div v-if="form.address" class="border-t border-gray-200 pt-3">
                            <dt class="text-gray-400">Address</dt>
                            <dd class="mt-1 whitespace-pre-line text-gray-800">{{ form.address }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </div>
</template>
