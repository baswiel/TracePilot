<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CustomerForm from '@/components/customers/CustomerForm.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/customers';

const props = defineProps<{ customer: { id: number; name: string } }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Klanten', href: index() },
            { title: 'Klant bewerken', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`${customer.name} bewerken`" />

    <div class="mx-auto w-full max-w-3xl p-4 sm:p-6">
        <Card>
            <CardHeader><CardTitle>Klant bewerken</CardTitle></CardHeader>
            <CardContent>
                <CustomerForm
                    :customer="customer"
                    :cancel-href="show(customer.id).url"
                />
            </CardContent>
        </Card>
    </div>
</template>
