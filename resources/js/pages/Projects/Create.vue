<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProjectForm from '@/components/projects/ProjectForm.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index } from '@/routes/projects';

defineProps<{
    teamMembers: { id: number; name: string; email: string | null }[];
    slaLevels: { id: number; name: string }[];
    customers: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Projecten', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Project toevoegen" />

    <div class="mx-auto w-full max-w-3xl p-4 sm:p-6">
        <Card>
            <CardHeader><CardTitle>Project toevoegen</CardTitle></CardHeader>
            <CardContent
                ><ProjectForm
                    :cancel-href="index().url"
                    :team-members="teamMembers"
                    :sla-levels="slaLevels"
                    :customers="customers"
            /></CardContent>
        </Card>
    </div>
</template>
