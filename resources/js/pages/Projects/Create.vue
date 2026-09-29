<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProjectForm from '@/components/projects/ProjectForm.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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

    <div
        class="mx-auto flex w-full max-w-3xl flex-1 flex-col px-5 pt-2 pb-10 sm:px-8"
    >
        <Card>
            <CardHeader class="border-b">
                <CardTitle>Project toevoegen</CardTitle>
                <CardDescription>
                    Leg de klant, SLA en vaste responders in één keer vast.
                </CardDescription>
            </CardHeader>
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
