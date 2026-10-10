<script setup lang="ts">
import WorkspaceHeader from '@/components/WorkspaceHeader.vue';
import WorkspacePage from '@/components/WorkspacePage.vue';
import { Head } from '@inertiajs/vue3';
import ProjectForm from '@/components/projects/ProjectForm.vue';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/projects';

type Project = {
    id: number;
    name: string;
    customer_id: number | null;
    description: string | null;
    sla_level_id: number | null;
    sla_first_response_minutes: number | null;
    sla_resolution_minutes: number | null;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    first_responder_id: number | null;
    second_responder_id: number | null;
    third_responder_id: number | null;
    is_active: boolean;
};

defineProps<{
    project: Project;
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
    <Head :title="`${project.name} bewerken`" />

    <WorkspacePage compact>
        <WorkspaceHeader
            title="Project bewerken"
            description="Werk klantgegevens, SLA en responders bij."
        />
        <Card>
            <CardContent>
                <ProjectForm
                    :project="project"
                    :cancel-href="show(project.id).url"
                    :team-members="teamMembers"
                    :sla-levels="slaLevels"
                    :customers="customers"
                />
            </CardContent>
        </Card>
    </WorkspacePage>
</template>
