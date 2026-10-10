<script setup lang="ts">
import WorkspaceHeader from '@/components/WorkspaceHeader.vue';
import WorkspacePage from '@/components/WorkspacePage.vue';
import { Head } from '@inertiajs/vue3';
import CustomerForm from '@/components/customers/CustomerForm.vue';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/customers';

defineProps<{ customer: { id: number; name: string } }>();

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

    <WorkspacePage compact>
        <WorkspaceHeader
            title="Klant bewerken"
            description="Wijzig de naam waaronder projecten en storingen worden gegroepeerd."
        />
        <Card>
            <CardContent>
                <CustomerForm
                    :customer="customer"
                    :cancel-href="show(customer.id).url"
                />
            </CardContent>
        </Card>
    </WorkspacePage>
</template>
