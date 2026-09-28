<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CheckCircle2, CircleAlert, Clock3 } from '@lucide/vue';
import { computed, reactive } from 'vue';
import IssuePriorityBadge from '@/components/issues/IssuePriorityBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { show } from '@/routes/issues';

type Issue = {
    id: number;
    project: string;
    title: string;
    priority: 'p1' | 'p2' | 'p3' | 'p4';
    status: 'open' | 'handling';
    reported_at: string;
    assigned_to: string | null;
    checklist_completed: number;
    checklist_total: number;
    required_checklist_completed: number;
    required_checklist_total: number;
    sla: Sla;
};

type SlaMilestone = {
    target_minutes: number | null;
    deadline_at: string | null;
    state:
        | 'unavailable'
        | 'on_track'
        | 'at_risk'
        | 'overdue'
        | 'met'
        | 'breached';
    label: string;
    remaining_minutes: number | null;
};

type Sla = {
    response: SlaMilestone;
    resolution: SlaMilestone;
    needs_attention: boolean;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedIssues = {
    data: Issue[];
    current_page: number;
    last_page: number;
    links: PaginationLink[];
};

type SelectOption = { id: number; name: string };

const props = defineProps<{
    statistics: {
        open: number;
        handling: number;
        completed_this_month: number;
        sla_attention: number;
    };
    issues: PaginatedIssues;
    filters: {
        project: number | '';
        priority: Issue['priority'] | '';
        status: Issue['status'] | '';
        assigned_to: number | '';
    };
    projects: SelectOption[];
    teamMembers: SelectOption[];
}>();

const filters = reactive({ ...props.filters });
const page = usePage();
const firstName = computed(
    () => page.props.auth.user.name.split(' ').at(0) ?? 'daar',
);

const priorityDistribution = computed(() => ({
    p1: props.issues.data.filter((issue) => issue.priority === 'p1').length,
    p2: props.issues.data.filter((issue) => issue.priority === 'p2').length,
    p3: props.issues.data.filter((issue) => issue.priority === 'p3').length,
}));

const totalVisibleIssues = computed(
    () =>
        priorityDistribution.value.p1 +
        priorityDistribution.value.p2 +
        priorityDistribution.value.p3,
);

const priorityGradient = computed(() => {
    const total = totalVisibleIssues.value || 1;
    const p1 = (priorityDistribution.value.p1 / total) * 100;
    const p2 = p1 + (priorityDistribution.value.p2 / total) * 100;

    return `conic-gradient(#ef3340 0 ${p1}%, #f58a07 ${p1}% ${p2}%, #76859d ${p2}% 100%)`;
});

const applyFilters = () => {
    router.get(dashboard.url(), filters, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const formatDate = (value: string) =>
    new Intl.DateTimeFormat('nl-NL', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));

const elapsedSince = (value: string) => {
    const minutes = Math.max(
        0,
        Math.floor((Date.now() - new Date(value).getTime()) / 60000),
    );

    if (minutes < 60) return `${minutes} minuten`;
    if (minutes < 1440) return `${Math.floor(minutes / 60)} uur`;

    return `${Math.floor(minutes / 1440)} dag`;
};

const progressWidth = (issue: Issue) =>
    issue.checklist_total === 0
        ? 0
        : Math.round((issue.checklist_completed / issue.checklist_total) * 100);

const slaClass = (sla: Sla) => {
    const states = [sla.response.state, sla.resolution.state];

    if (states.some((state) => state === 'overdue' || state === 'breached')) {
        return 'bg-red-50 text-red-700';
    }

    if (states.includes('at_risk')) return 'bg-orange-50 text-orange-700';
    if (states.includes('unavailable')) return 'bg-slate-100 text-slate-600';

    return 'bg-emerald-50 text-emerald-700';
};

const slaLabel = (sla: Sla) => {
    const milestones = [sla.response, sla.resolution];
    const critical = milestones.find((milestone) =>
        ['overdue', 'breached', 'at_risk'].includes(milestone.state),
    );

    return (
        critical?.label ??
        milestones.find((milestone) => milestone.target_minutes)?.label ??
        'Geen SLA'
    );
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Overzicht', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-6 px-5 pt-2 pb-10 sm:px-8"
    >
        <section class="space-y-1 pt-1">
            <h1
                class="text-[clamp(1.875rem,3vw,2.5rem)] font-semibold tracking-[-0.035em] text-[#101d3f]"
            >
                Goedemiddag, {{ firstName }}
            </h1>
            <p class="text-muted-foreground">
                Dit speelt er momenteel binnen je projecten.
            </p>
        </section>

        <section class="grid gap-5 lg:grid-cols-3">
            <Card
                class="relative min-h-36 overflow-hidden border-l-4 border-l-red-500 py-0"
            >
                <CardContent class="flex items-center gap-5 p-6">
                    <span
                        class="grid size-15 shrink-0 place-items-center rounded-full bg-red-50 text-red-500"
                    >
                        <CircleAlert class="size-8" />
                    </span>
                    <div>
                        <p class="text-sm font-medium text-[#101d3f]">
                            Actieve storingen
                        </p>
                        <p
                            class="mt-1 text-4xl font-semibold tracking-[-0.04em] text-red-500"
                        >
                            {{ statistics.open }}
                        </p>
                        <CardDescription class="mt-1"
                            >{{
                                priorityDistribution.p1
                            }}
                            kritiek</CardDescription
                        >
                    </div>
                </CardContent>
            </Card>
            <Card
                class="relative min-h-36 overflow-hidden border-l-4 border-l-orange-500 py-0"
            >
                <CardContent class="flex items-center gap-5 p-6">
                    <span
                        class="grid size-15 shrink-0 place-items-center rounded-full bg-orange-50 text-orange-500"
                    >
                        <Clock3 class="size-8" />
                    </span>
                    <div>
                        <p class="text-sm font-medium text-[#101d3f]">
                            In afhandeling
                        </p>
                        <p
                            class="mt-1 text-4xl font-semibold tracking-[-0.04em] text-orange-500"
                        >
                            {{ statistics.handling }}
                        </p>
                        <CardDescription class="mt-1"
                            >Technisch opgelost</CardDescription
                        >
                    </div>
                </CardContent>
            </Card>
            <Card
                class="relative min-h-36 overflow-hidden border-l-4 border-l-emerald-600 py-0"
            >
                <CardContent class="flex items-center gap-5 p-6">
                    <span
                        class="grid size-15 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600"
                    >
                        <CheckCircle2 class="size-8" />
                    </span>
                    <div>
                        <p class="text-sm font-medium text-[#101d3f]">
                            Afgerond deze maand
                        </p>
                        <p
                            class="mt-1 text-4xl font-semibold tracking-[-0.04em] text-emerald-600"
                        >
                            {{ statistics.completed_this_month }}
                        </p>
                        <CardDescription class="mt-1"
                            >+2 t.o.v. vorige maand</CardDescription
                        >
                    </div>
                </CardContent>
            </Card>
        </section>

        <Card class="gap-0 overflow-hidden py-0">
            <CardHeader
                class="flex flex-col gap-5 border-b px-6 py-5 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <CardTitle class="text-xl text-[#101d3f]"
                        >Huidige issues</CardTitle
                    >
                    <CardDescription>
                        Storingen die nog aandacht nodig hebben.
                    </CardDescription>
                </div>
                <form
                    class="grid w-full gap-3 sm:grid-cols-2 xl:w-auto xl:grid-cols-2"
                    @submit.prevent="applyFilters"
                >
                    <div class="grid gap-1">
                        <Label class="sr-only" for="project">Project</Label>
                        <select
                            id="project"
                            v-model="filters.project"
                            @change="applyFilters"
                            class="border-input bg-background h-11 min-w-40 rounded-lg border px-3 text-sm shadow-xs"
                        >
                            <option value="">Alle projecten</option>
                            <option
                                v-for="project in projects"
                                :key="project.id"
                                :value="project.id"
                            >
                                {{ project.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-1">
                        <Label class="sr-only" for="status">Status</Label>
                        <select
                            id="status"
                            v-model="filters.status"
                            @change="applyFilters"
                            class="border-input bg-background h-11 min-w-40 rounded-lg border px-3 text-sm shadow-xs"
                        >
                            <option value="">Alle statussen</option>
                            <option value="open">Open</option>
                            <option value="handling">
                                Opgelost / afhandeling
                            </option>
                        </select>
                    </div>
                </form>
            </CardHeader>
            <CardContent class="space-y-5 p-0">
                <div v-if="issues.data.length" class="overflow-x-auto">
                    <table class="w-full min-w-[850px] text-left text-sm">
                        <thead class="text-muted-foreground bg-[#fcfdff]">
                            <tr>
                                <th class="px-6 py-4 font-medium">Project</th>
                                <th class="px-6 py-4 font-medium">Storing</th>
                                <th class="px-6 py-4 font-medium">
                                    Prioriteit
                                </th>
                                <th class="px-6 py-4 font-medium">Status</th>
                                <th class="px-6 py-4 font-medium">
                                    Open sinds
                                </th>
                                <th class="px-6 py-4 font-medium">SLA</th>
                                <th class="px-6 py-4 font-medium">Checklist</th>
                                <th class="px-6 py-4">
                                    <span class="sr-only">Actie</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                class="transition-colors hover:bg-[#fafcff]"
                                v-for="issue in issues.data"
                                :key="issue.id"
                            >
                                <td class="px-6 py-4">{{ issue.project }}</td>
                                <td class="max-w-xs px-6 py-4 font-medium">
                                    {{ issue.title }}
                                </td>
                                <td class="px-6 py-4">
                                    <IssuePriorityBadge
                                        :priority="issue.priority"
                                    />
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-2"
                                    >
                                        <span
                                            :class="
                                                issue.status === 'open'
                                                    ? 'bg-red-500'
                                                    : 'bg-orange-500'
                                            "
                                            class="size-2 rounded-full"
                                        />
                                        {{
                                            issue.status === 'open'
                                                ? 'Open'
                                                : 'Afhandeling'
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="text-muted-foreground px-6 py-4 whitespace-nowrap"
                                >
                                    {{ elapsedSince(issue.reported_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="slaClass(issue.sla)"
                                    >
                                        {{ slaLabel(issue.sla) }}
                                    </span>
                                </td>
                                <td
                                    class="text-muted-foreground px-6 py-4 whitespace-nowrap"
                                >
                                    <span class="block text-[#101d3f]"
                                        >{{ issue.checklist_completed }} van
                                        {{ issue.checklist_total }}</span
                                    >
                                    <span
                                        class="mt-1.5 block h-1.5 w-32 overflow-hidden rounded-full bg-[#e4e8ef]"
                                        ><span
                                            :class="
                                                issue.status === 'open'
                                                    ? 'bg-red-500'
                                                    : 'bg-orange-500'
                                            "
                                            :style="{
                                                width: `${progressWidth(issue)}%`,
                                            }"
                                            class="block h-full rounded-full"
                                    /></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Button
                                        class="border-blue-500 text-blue-600 hover:bg-blue-50"
                                        size="sm"
                                        variant="outline"
                                        as-child
                                        ><Link :href="show(issue.id)"
                                            >Bekijken</Link
                                        ></Button
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-else
                    class="flex flex-col items-center gap-2 px-5 py-12 text-center"
                >
                    <CheckCircle2 class="text-muted-foreground size-6" />
                    <p class="text-sm font-medium">Geen huidige issues</p>
                    <p class="text-muted-foreground text-sm">
                        Er zijn geen open storingen of openstaande nazorgacties
                        voor deze filters.
                    </p>
                </div>

                <nav
                    v-if="issues.last_page > 1"
                    class="flex flex-wrap justify-end gap-1 px-6 pb-6"
                    aria-label="Paginering"
                >
                    <template v-for="link in issues.links" :key="link.label">
                        <Button
                            v-if="link.url"
                            size="sm"
                            :variant="link.active ? 'default' : 'outline'"
                            as-child
                        >
                            <Link :href="link.url" v-html="link.label" />
                        </Button>
                    </template>
                </nav>
            </CardContent>
        </Card>

        <section class="grid gap-5 lg:grid-cols-2">
            <Card class="min-h-52 py-0">
                <CardContent class="p-6">
                    <h2
                        class="text-lg font-semibold tracking-tight text-[#101d3f]"
                    >
                        Verdeling op prioriteit
                    </h2>
                    <div
                        class="mt-5 flex flex-col items-center gap-7 sm:flex-row sm:justify-center"
                    >
                        <div
                            class="relative size-30 rounded-full"
                            :style="{ background: priorityGradient }"
                        >
                            <div
                                class="text-muted-foreground absolute inset-7 grid place-items-center rounded-full bg-white text-center text-xs font-medium"
                            >
                                {{ totalVisibleIssues }} issues
                            </div>
                        </div>
                        <dl class="w-full max-w-60 space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <dt>
                                    <span
                                        class="mr-2 inline-block size-2.5 rounded-full bg-red-500"
                                    />P1 Kritiek
                                </dt>
                                <dd class="font-semibold">
                                    {{ priorityDistribution.p1 }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>
                                    <span
                                        class="mr-2 inline-block size-2.5 rounded-full bg-orange-500"
                                    />P2 Hoog
                                </dt>
                                <dd class="font-semibold">
                                    {{ priorityDistribution.p2 }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>
                                    <span
                                        class="mr-2 inline-block size-2.5 rounded-full bg-slate-500"
                                    />P3 Normaal
                                </dt>
                                <dd class="font-semibold">
                                    {{ priorityDistribution.p3 }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </CardContent>
            </Card>
            <Card class="min-h-52 py-0">
                <CardContent class="p-6">
                    <h2
                        class="text-lg font-semibold tracking-tight text-[#101d3f]"
                    >
                        Aandacht nodig
                    </h2>
                    <div
                        class="mt-5 flex min-h-30 items-center gap-4 rounded-xl border border-orange-300 bg-orange-50/70 p-5"
                    >
                        <CircleAlert class="size-10 shrink-0 text-orange-500" />
                        <div>
                            <p class="font-semibold text-[#101d3f]">
                                {{ statistics.sla_attention }} issues vragen
                                SLA-aandacht
                            </p>
                            <p class="text-muted-foreground mt-1 text-sm">
                                De reactietijd of oplostijd verloopt binnenkort
                                of is al overschreden.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
