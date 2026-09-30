<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ClipboardList,
    Download,
    Search,
    SlidersHorizontal,
    ArrowDownUp,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import IssuePriorityBadge from '@/components/issues/IssuePriorityBadge.vue';
import IssueSlaBadge from '@/components/issues/IssueSlaBadge.vue';
import IssueStatusBadge from '@/components/issues/IssueStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { exportMethod, index, report, show } from '@/routes/issues';

type Issue = {
    id: number;
    project: string;
    customer: string | null;
    title: string;
    priority: 'p1' | 'p2' | 'p3' | 'p4';
    status: 'open' | 'handling' | 'completed';
    reported_at: string;
    first_responded_at: string | null;
    resolved_at: string | null;
    assigned_to: string | null;
    completed_at: string | null;
    last_activity_at: string | null;
    sla: Sla;
};

type SlaMilestone = {
    target_minutes: number | null;
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

type Sla = { response: SlaMilestone; resolution: SlaMilestone };

type Pagination = {
    data: Issue[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type SelectOption = { id: number; name: string };

const props = defineProps<{
    issues: Pagination;
    filters: {
        search: string;
        project: number | '';
        customer: number | '';
        priority: Issue['priority'] | '';
        status: Issue['status'] | '';
        assigned_to: number | '';
        from: string;
        until: string;
        sort: 'priority' | 'reported_at' | 'last_activity';
        direction: 'asc' | 'desc';
    };
    projects: SelectOption[];
    customers: SelectOption[];
    teamMembers: SelectOption[];
}>();

const filters = reactive({ ...props.filters });
const showMoreFilters = ref(
    Boolean(
        props.filters.customer ||
        props.filters.assigned_to ||
        props.filters.from ||
        props.filters.until,
    ),
);
const activeFilterCount = computed(
    () => Object.values(filters).filter((value) => value !== '').length,
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Alle storingen', href: index() },
        ],
    },
});

const applyFilters = () => {
    router.get(index().url, filters, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    Object.assign(filters, {
        search: '',
        project: '',
        customer: '',
        priority: '',
        status: '',
        assigned_to: '',
        from: '',
        until: '',
    });
    applyFilters();
};

const toggleSort = (sort: typeof filters.sort) => {
    filters.direction =
        filters.sort === sort && filters.direction === 'asc' ? 'desc' : 'asc';
    filters.sort = sort;
    applyFilters();
};

const sortLabel = (sort: typeof filters.sort) =>
    filters.sort === sort
        ? `Sorteer ${filters.direction === 'asc' ? 'aflopend' : 'oplopend'}`
        : 'Sorteer op deze kolom';

const exportUrl = () => exportMethod({ query: { ...filters } }).url;

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

    if (minutes < 60) return `${minutes} min`;
    if (minutes < 1440) return `${Math.floor(minutes / 60)} u`;

    return `${Math.floor(minutes / 1440)} d`;
};

const truncateTitle = (title: string) => {
    const words = title.trim().split(/\s+/);

    return words.length > 8 ? `${words.slice(0, 8).join(' ')}...` : title;
};
</script>

<template>
    <Head title="Alle storingen" />

    <div
        class="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-6 px-5 pt-2 pb-10 sm:px-8"
    >
        <section
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Alle storingen
                </h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Doorzoek en beheer alle gemelde storingen, inclusief
                    afgeronde meldingen.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <a :href="exportUrl()"><Download /> Exporteer CSV</a>
                </Button>
                <Button as-child
                    ><Link :href="report()">Storing melden</Link></Button
                >
            </div>
        </section>

        <Card class="gap-0 overflow-hidden py-0">
            <CardContent class="p-4 sm:p-5">
                <form
                    class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(16rem,1fr)_11rem_9rem_10rem_auto]"
                    @submit.prevent="applyFilters"
                >
                    <div class="relative md:col-span-2 xl:col-span-1">
                        <Label class="sr-only" for="issue-search">Zoeken</Label>
                        <Search
                            class="text-muted-foreground absolute top-3 left-3 size-4"
                        />
                        <Input
                            id="issue-search"
                            v-model="filters.search"
                            class="h-10 pl-9"
                            placeholder="Zoek op storing, omschrijving of project"
                        />
                    </div>
                    <div class="grid gap-1">
                        <Label class="sr-only" for="filter-project"
                            >Project</Label
                        >
                        <select
                            id="filter-project"
                            v-model="filters.project"
                            class="border-input bg-background h-10 rounded-md border px-3 text-sm shadow-xs"
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
                        <Label class="sr-only" for="filter-priority"
                            >Prioriteit</Label
                        >
                        <select
                            id="filter-priority"
                            v-model="filters.priority"
                            class="border-input bg-background h-10 rounded-md border px-3 text-sm shadow-xs"
                        >
                            <option value="">Alle prioriteiten</option>
                            <option value="p1">P1 · Kritiek</option>
                            <option value="p2">P2 · Hoog</option>
                            <option value="p3">P3 · Normaal</option>
                            <option value="p4">P4 · Laag</option>
                        </select>
                    </div>
                    <div class="grid gap-1">
                        <Label class="sr-only" for="filter-status"
                            >Status</Label
                        >
                        <select
                            id="filter-status"
                            v-model="filters.status"
                            class="border-input bg-background h-10 rounded-md border px-3 text-sm shadow-xs"
                        >
                            <option value="">Alle statussen</option>
                            <option value="open">Open</option>
                            <option value="handling">Afhandeling</option>
                            <option value="completed">Afgerond</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <Button type="submit">Filteren</Button
                        ><Button
                            type="button"
                            variant="outline"
                            @click="showMoreFilters = !showMoreFilters"
                            ><SlidersHorizontal />
                            {{
                                showMoreFilters ? 'Minder' : 'Meer filters'
                            }}</Button
                        >
                    </div>
                </form>
                <div
                    v-if="showMoreFilters"
                    class="mt-3 grid gap-3 border-t pt-3 md:grid-cols-2 xl:grid-cols-4"
                >
                    <div class="grid gap-1">
                        <Label class="sr-only" for="filter-customer"
                            >Klant</Label
                        >
                        <select
                            id="filter-customer"
                            v-model="filters.customer"
                            class="border-input bg-background h-10 rounded-md border px-3 text-sm shadow-xs"
                        >
                            <option value="">Alle klanten</option>
                            <option
                                v-for="customer in customers"
                                :key="customer.id"
                                :value="customer.id"
                            >
                                {{ customer.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-1">
                        <Label class="sr-only" for="filter-assignee"
                            >Verantwoordelijke</Label
                        >
                        <select
                            id="filter-assignee"
                            v-model="filters.assigned_to"
                            class="border-input bg-background h-10 rounded-md border px-3 text-sm shadow-xs"
                        >
                            <option value="">Iedere verantwoordelijke</option>
                            <option
                                v-for="teamMember in teamMembers"
                                :key="teamMember.id"
                                :value="teamMember.id"
                            >
                                {{ teamMember.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-1">
                        <label
                            class="text-muted-foreground text-xs font-medium"
                            for="from"
                            >Gemeld vanaf</label
                        >
                        <Input id="from" v-model="filters.from" type="date" />
                    </div>
                    <div class="grid gap-1">
                        <label
                            class="text-muted-foreground text-xs font-medium"
                            for="until"
                            >Gemeld tot en met</label
                        >
                        <Input id="until" v-model="filters.until" type="date" />
                    </div>
                </div>
                <div
                    v-if="activeFilterCount"
                    class="mt-4 flex items-center gap-3 text-sm"
                >
                    <span class="text-muted-foreground"
                        >{{ activeFilterCount }} actieve
                        {{
                            activeFilterCount === 1 ? 'filter' : 'filters'
                        }}</span
                    >
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="clearFilters"
                        >Filters wissen</Button
                    >
                </div>
            </CardContent>
        </Card>

        <Card class="gap-0 overflow-hidden py-0">
            <CardContent v-if="issues.data.length" class="p-0">
                <p class="text-muted-foreground px-4 pt-3 text-xs sm:hidden">
                    Veeg horizontaal om alle incidentgegevens te bekijken.
                </p>
                <div class="overflow-x-auto">
                    <table
                        class="w-full min-w-[1150px] text-left text-sm"
                        aria-label="Storingenoverzicht"
                    >
                        <thead class="text-muted-foreground bg-[#fcfdff]">
                            <tr>
                                <th class="px-6 py-4 font-medium">Storing</th>
                                <th class="px-6 py-4 font-medium">Project</th>
                                <th class="px-6 py-4 font-medium">
                                    <button
                                        class="hover:text-foreground inline-flex items-center gap-1.5"
                                        type="button"
                                        :aria-label="sortLabel('priority')"
                                        @click="toggleSort('priority')"
                                    >
                                        Prioriteit
                                        <ArrowDownUp
                                            class="size-3.5"
                                            :class="
                                                filters.sort === 'priority'
                                                    ? 'text-primary'
                                                    : ''
                                            "
                                        />
                                    </button>
                                </th>
                                <th class="px-6 py-4 font-medium">Status</th>
                                <th class="px-6 py-4 font-medium">
                                    <button
                                        class="hover:text-foreground inline-flex items-center gap-1.5"
                                        type="button"
                                        :aria-label="sortLabel('reported_at')"
                                        @click="toggleSort('reported_at')"
                                    >
                                        Gemeld op
                                        <ArrowDownUp
                                            class="size-3.5"
                                            :class="
                                                filters.sort === 'reported_at'
                                                    ? 'text-primary'
                                                    : ''
                                            "
                                        />
                                    </button>
                                </th>
                                <th class="px-6 py-4 font-medium">SLA</th>
                                <th class="px-6 py-4 font-medium">
                                    Toegewezen aan
                                </th>
                                <th class="px-6 py-4 font-medium">
                                    <button
                                        class="hover:text-foreground inline-flex items-center gap-1.5"
                                        type="button"
                                        :aria-label="sortLabel('last_activity')"
                                        @click="toggleSort('last_activity')"
                                    >
                                        Laatste activiteit
                                        <ArrowDownUp
                                            class="size-3.5"
                                            :class="
                                                filters.sort === 'last_activity'
                                                    ? 'text-primary'
                                                    : ''
                                            "
                                        />
                                    </button>
                                </th>
                                <th class="px-6 py-4">
                                    <span class="sr-only">Actie</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="issue in issues.data"
                                :key="issue.id"
                                class="transition-colors hover:bg-[#fafcff]"
                            >
                                <td
                                    class="w-[18rem] min-w-[18rem] px-6 py-4 font-medium text-[#101d3f]"
                                >
                                    <span
                                        class="block truncate whitespace-nowrap"
                                        :aria-label="issue.title"
                                        :title="issue.title"
                                    >
                                        {{ truncateTitle(issue.title) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="block font-medium text-[#101d3f]"
                                    >
                                        {{ issue.project }}
                                    </span>
                                    <span
                                        v-if="issue.customer"
                                        class="text-muted-foreground mt-0.5 block text-xs"
                                    >
                                        {{ issue.customer }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <IssuePriorityBadge
                                        :priority="issue.priority"
                                    />
                                </td>
                                <td class="px-6 py-4">
                                    <IssueStatusBadge :status="issue.status" />
                                </td>
                                <td
                                    class="text-muted-foreground px-6 py-4 whitespace-nowrap"
                                >
                                    <span
                                        :title="formatDate(issue.reported_at)"
                                    >
                                        {{ elapsedSince(issue.reported_at) }}
                                        geleden
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <IssueSlaBadge
                                        :response="issue.sla.response"
                                        :resolution="issue.sla.resolution"
                                    />
                                </td>
                                <td class="text-muted-foreground px-6 py-4">
                                    {{ issue.assigned_to ?? 'Niet toegewezen' }}
                                </td>
                                <td
                                    class="text-muted-foreground px-6 py-4 whitespace-nowrap"
                                >
                                    {{
                                        issue.last_activity_at
                                            ? formatDate(issue.last_activity_at)
                                            : '—'
                                    }}
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
            </CardContent>
            <CardContent
                v-else
                class="flex flex-col items-center gap-3 px-6 py-16 text-center"
                ><span class="bg-muted rounded-full p-3"
                    ><ClipboardList class="text-muted-foreground size-6"
                /></span>
                <div>
                    <h2 class="font-medium">Geen storingen gevonden</h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        {{
                            activeFilterCount
                                ? 'Pas je filters aan of wis ze om opnieuw te zoeken.'
                                : 'Er zijn nog geen storingen gemeld. Registreer de eerste storing om het overzicht te starten.'
                        }}
                    </p>
                </div>
                <Button
                    v-if="activeFilterCount"
                    variant="outline"
                    @click="clearFilters"
                    >Filters wissen</Button
                >
                <Button v-else as-child
                    ><Link :href="report()">Storing melden</Link></Button
                ></CardContent
            >
            <div
                v-if="issues.last_page > 1"
                class="flex items-center justify-between border-t px-6 py-4"
            >
                <p class="text-muted-foreground text-sm">
                    {{ issues.total }} storingen
                </p>
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!issues.prev_page_url"
                        as-child
                        ><Link
                            v-if="issues.prev_page_url"
                            :href="issues.prev_page_url"
                            >Vorige</Link
                        ><span v-else>Vorige</span></Button
                    ><Button
                        variant="outline"
                        size="sm"
                        :disabled="!issues.next_page_url"
                        as-child
                        ><Link
                            v-if="issues.next_page_url"
                            :href="issues.next_page_url"
                            >Volgende</Link
                        ><span v-else>Volgende</span></Button
                    >
                </div>
            </div>
        </Card>
    </div>
</template>
