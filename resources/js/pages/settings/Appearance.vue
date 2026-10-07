<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Weergave',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Weergave" />

    <h1 class="sr-only">Weergave</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Weergave"
            description="Kies een lichte of donkere weergave, of volg je systeeminstelling."
        />
        <AppearanceTabs />
    </div>
</template>
