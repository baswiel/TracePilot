<script setup lang="ts">
import WorkspaceHeader from '@/components/WorkspaceHeader.vue';
import WorkspacePage from '@/components/WorkspacePage.vue';
import { Head } from '@inertiajs/vue3';
import ProjectForm from '@/components/projects/ProjectForm.vue';
import { Card, CardContent } from '@/components/ui/card';
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

    <WorkspacePage compact>
        <WorkspaceHeader
            title="Project toevoegen"
            description="Leg de klant, SLA en vaste responders in één keer vast."
        />
        <Card>
            <CardContent
                ><ProjectForm
                    :cancel-href="index().url"
                    :team-members="teamMembers"
                    :sla-levels="slaLevels"
                    :customers="customers"
            /></CardContent>
        </Card>
    </WorkspacePage>
</template>
