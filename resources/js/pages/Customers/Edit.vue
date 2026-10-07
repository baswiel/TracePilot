<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CustomerForm from '@/components/customers/CustomerForm.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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

    <div
        class="mx-auto flex w-full max-w-3xl flex-1 flex-col px-5 pt-7 pb-10 sm:px-8"
    >
        <Card>
            <CardHeader class="border-b">
                <CardTitle>Klant bewerken</CardTitle>
                <CardDescription>
                    Wijzig de naam waaronder projecten en storingen worden
                    gegroepeerd.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <CustomerForm
                    :customer="customer"
                    :cancel-href="show(customer.id).url"
                />
            </CardContent>
        </Card>
    </div>
</template>
