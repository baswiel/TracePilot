<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, FileText, Pencil, Plus } from '@lucide/vue';
import { ref } from 'vue';
import IssuePriorityBadge from '@/components/issues/IssuePriorityBadge.vue';
import IssueStatusBadge from '@/components/issues/IssueStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { report, show as showIssue } from '@/routes/issues';
import projectRoutes, { edit, index } from '@/routes/projects';

type Issue = {
    id: number;
    title: string;
    priority: 'p1' | 'p2' | 'p3' | 'p4';
    status: 'open' | 'handling' | 'completed';
    reported_at: string;
    completed_at: string | null;
    duration_minutes: number | null;
};

type Project = {
    id: number;
    name: string;
    customer_name: string | null;
    description: string | null;
    sla_level: {
        id: number;
        name: string;
        targets: Array<{
            priority: 'p1' | 'p2' | 'p3' | 'p4';
            response_minutes: number;
            resolution_minutes: number;
        }>;
    } | null;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    first_responder: { id: number; name: string; email: string | null } | null;
    second_responder: { id: number; name: string; email: string | null } | null;
    is_active: boolean;
    created_at: string;
};

const props = defineProps<{
    project: Project;
    currentIssues: Issue[];
    completedIssues: Issue[];
}>();
const isUpdatingActive = ref(false);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Projecten', href: index() },
        ],
    },
});

const setActive = () => {
    if (
        props.project.is_active &&
        !window.confirm(
            `Wil je '${props.project.name}' archiveren? Nieuwe storingen kunnen dan niet meer aan dit project worden gekoppeld.`,
        )
    ) {
        return;
    }

    router.patch(
        projectRoutes.active.update(props.project.id).url,
        { is_active: !props.project.is_active },
        {
            preserveScroll: true,
            onStart: () => {
                isUpdatingActive.value = true;
            },
            onFinish: () => {
                isUpdatingActive.value = false;
            },
        },
    );
};

const formatDate = (value: string) =>
    new Intl.DateTimeFormat('nl-NL', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));

const formatDuration = (minutes: number) => {
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    return hours > 0
        ? `${hours} u ${remainingMinutes} min`
        : `${remainingMinutes} min`;
};
</script>

<template>
    <Head :title="project.name" />

    <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4 sm:p-6">
        <section
            class="bg-card flex flex-col gap-4 rounded-xl border p-5 shadow-sm sm:flex-row sm:items-start sm:justify-between sm:p-6"
        >
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ project.name }}
                    </h1>
                    <Badge
                        :variant="project.is_active ? 'default' : 'secondary'"
                    >
                        {{ project.is_active ? 'Actief' : 'Inactief' }}
                    </Badge>
                </div>
                <p class="text-muted-foreground text-sm">
                    {{ project.customer_name || 'Geen klantnaam opgegeven' }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="edit(project.id)"><Pencil /> Bewerken</Link>
                </Button>
                <Button
                    :disabled="isUpdatingActive"
                    variant="outline"
                    @click="setActive"
                >
                    {{
                        project.is_active
                            ? 'Archiveer project'
                            : 'Activeer project'
                    }}
                </Button>
                <Button as-child>
                    <Link :href="report()"><Plus /> Nieuwe storing</Link>
                </Button>
            </div>
        </section>

        <Card>
            <CardHeader>
                <CardTitle>Projectinformatie</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <p class="text-muted-foreground text-sm">Omschrijving</p>
                    <p class="mt-1 text-sm whitespace-pre-line">
                        {{
                            project.description ||
                            'Geen omschrijving opgegeven.'
                        }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground text-sm">Klant</p>
                    <p class="mt-1 text-sm">
                        {{ project.customer_name || '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground text-sm">Aangemaakt</p>
                    <p class="mt-1 text-sm">
                        {{ formatDate(project.created_at) }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <section class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>SLA</CardTitle>
                    <CardDescription>
                        Afspraken voor de afhandeling van storingen.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-5 sm:grid-cols-2">
                    <div v-if="project.sla_level" class="sm:col-span-2">
                        <p class="text-muted-foreground text-sm">
                            Niveau: {{ project.sla_level.name }}
                        </p>
                        <div class="mt-3 overflow-x-auto rounded-lg border">
                            <table class="w-full min-w-110 text-sm">
                                <thead
                                    class="text-muted-foreground bg-muted/40"
                                >
                                    <tr>
                                        <th
                                            class="px-3 py-2 text-left font-medium"
                                        >
                                            Prioriteit
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left font-medium"
                                        >
                                            Reactie
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left font-medium"
                                        >
                                            Oplossen
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="target in project.sla_level
                                            .targets"
                                        :key="target.priority"
                                    >
                                        <td class="px-3 py-2 font-medium">
                                            {{ target.priority.toUpperCase() }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{
                                                formatDuration(
                                                    target.response_minutes,
                                                )
                                            }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{
                                                formatDuration(
                                                    target.resolution_minutes,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <p
                        v-else
                        class="text-muted-foreground text-sm sm:col-span-2"
                    >
                        Geen SLA-niveau toegewezen.
                    </p>
                    <div>
                        <p class="text-muted-foreground text-sm">
                            Eerste responder
                        </p>
                        <p class="mt-1 text-sm font-medium">
                            {{
                                project.first_responder?.name ||
                                'Niet toegewezen'
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground text-sm">
                            Tweede responder
                        </p>
                        <p class="mt-1 text-sm font-medium">
                            {{
                                project.second_responder?.name ||
                                'Niet toegewezen'
                            }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Contactpersoon</CardTitle>
                    <CardDescription>
                        Aanspreekpunt voor dit project.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div>
                        <p class="text-muted-foreground text-sm">Naam</p>
                        <p class="mt-1 text-sm font-medium">
                            {{ project.contact_name || 'Niet opgegeven' }}
                        </p>
                    </div>
                    <div v-if="project.contact_email">
                        <p class="text-muted-foreground text-sm">E-mailadres</p>
                        <a
                            class="mt-1 block text-sm font-medium hover:underline"
                            :href="`mailto:${project.contact_email}`"
                        >
                            {{ project.contact_email }}
                        </a>
                    </div>
                    <div v-if="project.contact_phone">
                        <p class="text-muted-foreground text-sm">
                            Telefoonnummer
                        </p>
                        <a
                            class="mt-1 block text-sm font-medium hover:underline"
                            :href="`tel:${project.contact_phone}`"
                        >
                            {{ project.contact_phone }}
                        </a>
                    </div>
                    <p
                        v-if="!project.contact_email && !project.contact_phone"
                        class="text-muted-foreground text-sm"
                    >
                        Geen contactgegevens opgegeven.
                    </p>
                </CardContent>
            </Card>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Huidige storingen</CardTitle>
                    <CardDescription
                        >Nog niet volledig afgeronde storingen.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div
                        v-if="currentIssues.length"
                        class="divide-y rounded-lg border"
                    >
                        <div
                            v-for="issue in currentIssues"
                            :key="issue.id"
                            class="space-y-2 p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <Link
                                    class="font-medium hover:underline"
                                    :href="showIssue(issue.id)"
                                >
                                    {{ issue.title }}
                                </Link>
                                <IssuePriorityBadge
                                    :priority="issue.priority"
                                />
                            </div>
                            <div
                                class="text-muted-foreground flex flex-wrap gap-x-3 gap-y-1 text-sm"
                            >
                                <IssueStatusBadge :status="issue.status" />
                                <span
                                    >Gemeld
                                    {{ formatDate(issue.reported_at) }}</span
                                >
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex flex-col items-center gap-2 rounded-lg border border-dashed px-5 py-10 text-center"
                    >
                        <AlertCircle class="text-muted-foreground size-5" />
                        <p class="text-sm font-medium">
                            Geen huidige storingen
                        </p>
                        <p class="text-muted-foreground text-sm">
                            Nieuwe storingen verschijnen hier.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Recent afgerond</CardTitle>
                    <CardDescription
                        >De vijf meest recent afgeronde
                        storingen.</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div
                        v-if="completedIssues.length"
                        class="divide-y rounded-lg border"
                    >
                        <div
                            v-for="issue in completedIssues"
                            :key="issue.id"
                            class="space-y-2 p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <Link
                                    class="font-medium hover:underline"
                                    :href="showIssue(issue.id)"
                                >
                                    {{ issue.title }}
                                </Link>
                                <IssuePriorityBadge
                                    :priority="issue.priority"
                                />
                            </div>
                            <p class="text-muted-foreground text-sm">
                                Afgerond
                                {{
                                    formatDate(
                                        issue.completed_at ?? issue.reported_at,
                                    )
                                }}
                            </p>
                            <p class="text-muted-foreground text-sm">
                                Duur
                                {{
                                    formatDuration(issue.duration_minutes ?? 0)
                                }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex flex-col items-center gap-2 rounded-lg border border-dashed px-5 py-10 text-center"
                    >
                        <FileText class="text-muted-foreground size-5" />
                        <p class="text-sm font-medium">
                            Nog geen afgeronde storingen
                        </p>
                        <p class="text-muted-foreground text-sm">
                            Afgeronde storingen verschijnen hier.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
