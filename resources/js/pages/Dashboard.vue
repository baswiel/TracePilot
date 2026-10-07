<script setup lang="ts">
import type { IssuePriority } from '@/types/issues';
import { formatDate } from '@/lib/dates';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    CircleAlert,
    Clock3,
    Plus,
    SlidersHorizontal,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import IssuePriorityBadge from '@/components/issues/IssuePriorityBadge.vue';
import IssueSlaBadge from '@/components/issues/IssueSlaBadge.vue';
import IssueStatusBadge from '@/components/issues/IssueStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { index, report, show } from '@/routes/issues';

type Issue = {
    id: number;
    project: string;
    title: string;
    priority: IssuePriority;
    status: 'open' | 'handling';
    reported_at: string;
    assigned_to: string | null;
    checklist_completed: number;
    checklist_total: number;
    required_checklist_completed: number;
    required_checklist_total: number;
    sla: Sla;
};

import type { IssueSla as Sla } from '@/types/issues';
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
    p4: props.issues.data.filter((issue) => issue.priority === 'p4').length,
}));

const totalVisibleIssues = computed(
    () =>
        priorityDistribution.value.p1 +
        priorityDistribution.value.p2 +
        priorityDistribution.value.p3 +
        priorityDistribution.value.p4,
);

const hasFilters = computed(() =>
    Object.values(props.filters).some((value) => value !== ''),
);

const filtersExpanded = ref(hasFilters.value);

const clearFilters = () => {
    Object.assign(filters, {
        project: '',
        priority: '',
        status: '',
        assigned_to: '',
    });
    applyFilters();
};

const applyFilters = () => {
    router.get(dashboard.url(), filters, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

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
    issue.required_checklist_total === 0
        ? 0
        : Math.round(
              (issue.required_checklist_completed /
                  issue.required_checklist_total) *
                  100,
          );

defineOptions({
    inheritAttrs: false,
    layout: {
        breadcrumbs: [{ title: 'Overzicht', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="workspace-page mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-6 px-5 pt-7 pb-10 sm:px-8"
    >
        <section
            class="order-1 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div class="space-y-2">
                <h1
                    class="text-[clamp(1.875rem,3vw,2.5rem)] font-semibold tracking-[-0.035em]"
                >
                    Goedemiddag, {{ firstName }}
                </h1>
                <p class="text-muted-foreground text-sm">
                    Dit speelt er momenteel binnen je projecten.
                </p>
            </div>
            <Button as-child
                ><Link :href="report()"><Plus /> Storing melden</Link></Button
            >
        </section>

        <section
            v-if="statistics.sla_attention"
            class="tone-warning order-2 flex items-start gap-3 rounded-xl border p-4 lg:order-3"
            aria-label="SLA-aandacht"
        >
            <Clock3 class="mt-0.5 size-5 shrink-0" />
            <div>
                <h2 class="text-sm font-semibold">
                    {{ statistics.sla_attention }}
                    {{
                        statistics.sla_attention === 1
                            ? 'storing vraagt'
                            : 'storingen vragen'
                    }}
                    SLA-aandacht
                </h2>
                <p class="mt-1 text-sm">
                    De reactie- of oplostijd verloopt binnenkort of is al
                    overschreden. Controleer de SLA bij de storingen hieronder.
                </p>
            </div>
        </section>

        <Card class="order-3 gap-0 overflow-hidden py-0 lg:order-4">
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-5 sm:px-6"
            >
                <div>
                    <h2 class="text-lg font-semibold tracking-tight">
                        Werkvoorraad
                    </h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Open storingen en nazorg, op volgorde van prioriteit.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        class="lg:hidden"
                        :aria-expanded="filtersExpanded"
                        aria-controls="dashboard-filters"
                        @click="filtersExpanded = !filtersExpanded"
                        ><SlidersHorizontal /> Filters</Button
                    >
                    <Link
                        :href="index()"
                        class="text-primary inline-flex min-h-10 items-center gap-2 text-sm font-medium hover:underline"
                        >Alle storingen <ArrowRight class="size-4"
                    /></Link>
                </div>
            </div>
            <form
                id="dashboard-filters"
                :class="filtersExpanded ? 'grid' : 'hidden lg:grid'"
                class="bg-muted/30 grid gap-3 border-b p-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-[1fr_1fr_1fr_1.2fr_auto]"
                @submit.prevent="applyFilters"
            >
                <div class="grid min-w-0 gap-1.5">
                    <Label for="project" class="text-muted-foreground text-xs"
                        >Project</Label
                    >
                    <select
                        id="project"
                        v-model="filters.project"
                        class="bg-card h-10 w-full min-w-0 border px-3 text-sm"
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
                <div class="grid min-w-0 gap-1.5">
                    <Label for="status" class="text-muted-foreground text-xs"
                        >Status</Label
                    >
                    <select
                        id="status"
                        v-model="filters.status"
                        class="bg-card h-10 w-full min-w-0 border px-3 text-sm"
                    >
                        <option value="">Alle statussen</option>
                        <option value="open">Open</option>
                        <option value="handling">Afhandeling</option>
                    </select>
                </div>
                <div class="grid min-w-0 gap-1.5">
                    <Label for="priority" class="text-muted-foreground text-xs"
                        >Prioriteit</Label
                    >
                    <select
                        id="priority"
                        v-model="filters.priority"
                        class="bg-card h-10 w-full min-w-0 border px-3 text-sm"
                    >
                        <option value="">Alle prioriteiten</option>
                        <option value="p1">P1 · Kritiek</option>
                        <option value="p2">P2 · Hoog</option>
                        <option value="p3">P3 · Normaal</option>
                        <option value="p4">P4 · Laag</option>
                    </select>
                </div>
                <div class="grid min-w-0 gap-1.5">
                    <Label
                        for="assigned_to"
                        class="text-muted-foreground text-xs"
                        >Verantwoordelijke</Label
                    >
                    <select
                        id="assigned_to"
                        v-model="filters.assigned_to"
                        class="bg-card h-10 w-full min-w-0 border px-3 text-sm"
                    >
                        <option value="">Iedereen</option>
                        <option
                            v-for="member in teamMembers"
                            :key="member.id"
                            :value="member.id"
                        >
                            {{ member.name }}
                        </option>
                    </select>
                </div>
                <Button type="submit" variant="outline" class="self-end"
                    ><SlidersHorizontal /> Toepassen</Button
                >
            </form>
            <div
                v-if="hasFilters"
                class="flex items-center justify-between border-b px-6 py-2 text-sm"
            >
                <p class="text-muted-foreground">
                    Je bekijkt een gefilterde werkvoorraad.
                </p>
                <Button variant="ghost" size="sm" @click="clearFilters"
                    >Filters wissen</Button
                >
            </div>
            <div v-if="issues.data.length">
                <p class="text-muted-foreground px-5 pt-3 text-xs lg:hidden">
                    Schuif de tabel horizontaal voor alle gegevens.
                </p>
                <div
                    class="overflow-x-auto"
                    tabindex="0"
                    role="region"
                    aria-label="Werkvoorraad, horizontaal scrollbaar"
                >
                    <table class="w-full min-w-[1000px] text-left text-sm">
                        <thead class="bg-muted/30 text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 font-medium">
                                    Prioriteit
                                </th>
                                <th class="px-5 py-3 font-medium">
                                    Storing / project
                                </th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 font-medium">
                                    Verantwoordelijke
                                </th>
                                <th class="px-5 py-3 font-medium">SLA</th>
                                <th class="px-5 py-3 font-medium">Checklist</th>
                                <th class="px-5 py-3">
                                    <span class="sr-only">Actie</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="issue in issues.data"
                                :key="issue.id"
                                class="hover:bg-muted/40 transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <IssuePriorityBadge
                                        :priority="issue.priority"
                                    />
                                </td>
                                <td class="min-w-64 px-5 py-4">
                                    <Link
                                        :href="show(issue.id)"
                                        class="hover:text-primary font-semibold underline-offset-4 hover:underline"
                                        >{{ issue.title }}</Link
                                    >
                                    <p
                                        class="text-muted-foreground mt-1 text-xs"
                                    >
                                        {{ issue.project }} ·
                                        <span
                                            :title="
                                                formatDate(issue.reported_at)
                                            "
                                            >{{
                                                elapsedSince(issue.reported_at)
                                            }}
                                            sinds melding</span
                                        >
                                    </p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <IssueStatusBadge :status="issue.status" />
                                </td>
                                <td class="text-muted-foreground px-5 py-4">
                                    {{ issue.assigned_to ?? 'Niet toegewezen' }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <IssueSlaBadge
                                        :response="issue.sla.response"
                                        :resolution="issue.sla.resolution"
                                    />
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="text-xs tabular-nums"
                                        >{{
                                            issue.required_checklist_completed
                                        }}
                                        van
                                        {{ issue.required_checklist_total }}
                                        verplicht</span
                                    >
                                    <span
                                        class="bg-muted mt-2 block h-1.5 w-24 overflow-hidden rounded-full"
                                        role="progressbar"
                                        :aria-valuenow="progressWidth(issue)"
                                        :aria-valuemin="0"
                                        :aria-valuemax="100"
                                        :aria-label="`Verplichte checklist voor ${issue.title}`"
                                        ><span
                                            class="block h-full rounded-full"
                                            :class="
                                                issue.status === 'open'
                                                    ? 'bg-destructive'
                                                    : 'bg-warning'
                                            "
                                            :style="{
                                                width: `${progressWidth(issue)}%`,
                                            }"
                                    /></span>
                                    <span
                                        class="text-muted-foreground mt-1 block text-xs"
                                        >{{ issue.checklist_completed }}/{{
                                            issue.checklist_total
                                        }}
                                        totaal</span
                                    >
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <Button size="sm" variant="outline" as-child
                                        ><Link
                                            :href="show(issue.id)"
                                            :aria-label="`Bekijk ${issue.title}`"
                                            >Bekijken</Link
                                        ></Button
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div
                v-else
                class="flex flex-col items-center gap-3 px-5 py-14 text-center"
            >
                <span
                    class="grid size-12 place-items-center rounded-full"
                    :class="
                        hasFilters
                            ? 'bg-muted text-muted-foreground'
                            : 'tone-success'
                    "
                    ><CheckCircle2 class="size-6"
                /></span>
                <div>
                    <h3 class="font-semibold">
                        {{
                            hasFilters
                                ? 'Geen storingen voor deze filters'
                                : 'Je werkvoorraad is bijgewerkt'
                        }}
                    </h3>
                    <p class="text-muted-foreground mt-2 max-w-md text-sm">
                        {{
                            hasFilters
                                ? 'Pas de filters aan om andere storingen en nazorg te bekijken.'
                                : 'Er zijn geen open storingen of openstaande nazorgacties. Afgeronde meldingen vind je in het storingenoverzicht.'
                        }}
                    </p>
                </div>
                <Button
                    v-if="hasFilters"
                    variant="outline"
                    @click="clearFilters"
                    >Filters wissen</Button
                >
                <Button v-else variant="outline" as-child
                    ><Link :href="index()"
                        >Afgeronde storingen bekijken
                        <ArrowRight class="size-4" /></Link
                ></Button>
            </div>
            <nav
                v-if="issues.last_page > 1"
                class="flex flex-wrap items-center justify-between gap-3 border-t px-6 py-4"
                aria-label="Paginering"
            >
                <span class="text-muted-foreground text-xs"
                    >Pagina {{ issues.current_page }} van
                    {{ issues.last_page }}</span
                >
                <div class="flex flex-wrap gap-1">
                    <template v-for="link in issues.links" :key="link.label"
                        ><Button
                            v-if="link.url"
                            size="sm"
                            :variant="link.active ? 'default' : 'outline'"
                            as-child
                            ><Link
                                :href="link.url"
                                :aria-current="link.active ? 'page' : undefined"
                                v-html="link.label" /></Button
                    ></template>
                </div>
            </nav>
        </Card>

        <section
            aria-label="Overzicht van alle storingen"
            class="order-4 grid gap-5 lg:order-2 lg:grid-cols-3"
        >
            <Card class="border-l-destructive min-h-36 border-l-4 py-0">
                <CardContent
                    class="summary-content flex items-center justify-between gap-4 p-4 lg:items-start lg:p-6"
                >
                    <div class="summary-copy min-w-0 flex-1">
                        <p class="text-sm font-medium">Open storingen</p>
                        <p
                            class="summary-value mt-2 text-4xl font-semibold tracking-tight tabular-nums"
                        >
                            {{ statistics.open }}
                        </p>
                        <p
                            class="summary-caption text-muted-foreground mt-2 text-xs"
                        >
                            Nog niet technisch opgelost
                        </p>
                    </div>
                    <span
                        class="tone-danger grid size-10 shrink-0 place-items-center rounded-full"
                        ><CircleAlert class="size-5"
                    /></span>
                </CardContent>
            </Card>
            <Card class="border-l-warning min-h-36 border-l-4 py-0">
                <CardContent
                    class="summary-content flex items-center justify-between gap-4 p-4 lg:items-start lg:p-6"
                >
                    <div class="summary-copy min-w-0 flex-1">
                        <p class="text-sm font-medium">In afhandeling</p>
                        <p
                            class="summary-value mt-2 text-4xl font-semibold tracking-tight tabular-nums"
                        >
                            {{ statistics.handling }}
                        </p>
                        <p
                            class="summary-caption text-muted-foreground mt-2 text-xs"
                        >
                            Technisch opgelost · nazorg open
                        </p>
                    </div>
                    <span
                        class="tone-warning grid size-10 shrink-0 place-items-center rounded-full"
                        ><Clock3 class="size-5"
                    /></span>
                </CardContent>
            </Card>
            <Card class="border-l-success min-h-36 border-l-4 py-0">
                <CardContent
                    class="summary-content flex items-center justify-between gap-4 p-4 lg:items-start lg:p-6"
                >
                    <div class="summary-copy min-w-0 flex-1">
                        <p class="text-sm font-medium">Afgerond deze maand</p>
                        <p
                            class="summary-value mt-2 text-4xl font-semibold tracking-tight tabular-nums"
                        >
                            {{ statistics.completed_this_month }}
                        </p>
                        <p
                            class="summary-caption text-muted-foreground mt-2 text-xs"
                        >
                            Verplichte checklist afgerond
                        </p>
                    </div>
                    <span
                        class="tone-success grid size-10 shrink-0 place-items-center rounded-full"
                        ><CheckCircle2 class="size-5"
                    /></span>
                </CardContent>
            </Card>
        </section>

        <section
            class="order-5 flex flex-col gap-4 px-1 sm:flex-row sm:items-center sm:justify-between"
            aria-label="Prioriteiten op deze pagina"
        >
            <div>
                <h2 class="text-sm font-semibold">
                    Prioriteiten in deze werkvoorraad
                </h2>
                <p class="text-muted-foreground mt-1 text-xs">
                    {{ totalVisibleIssues }} storingen op deze pagina · na
                    toepassing van filters
                </p>
            </div>
            <dl class="flex flex-wrap gap-x-6 gap-y-2 text-xs">
                <div
                    v-for="(count, priority) in priorityDistribution"
                    :key="priority"
                    class="flex items-center gap-2"
                >
                    <dt><IssuePriorityBadge :priority="priority" /></dt>
                    <dd class="font-semibold tabular-nums">{{ count }}</dd>
                </div>
            </dl>
        </section>
    </div>
</template>
