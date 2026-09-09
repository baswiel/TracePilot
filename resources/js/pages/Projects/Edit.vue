<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProjectForm from '@/components/projects/ProjectForm.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

    <div class="mx-auto w-full max-w-3xl p-4 sm:p-6">
        <Card>
            <CardHeader><CardTitle>Project bewerken</CardTitle></CardHeader>
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
    </div>
</template>
