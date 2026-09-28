<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ClipboardList, Download, Search } from '@lucide/vue';
import { reactive } from 'vue';
import IssuePriorityBadge from '@/components/issues/IssuePriorityBadge.vue';
import IssueStatusBadge from '@/components/issues/IssueStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { exportMethod, index, report, show } from '@/routes/issues';

type Issue = {
    id: number;
    project: string;
    title: string;
    priority: 'p1' | 'p2' | 'p3' | 'p4';
    status: 'open' | 'handling' | 'completed';
    reported_at: string;
    assigned_to: string | null;
    completed_at: string | null;
};

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
        priority: Issue['priority'] | '';
        status: Issue['status'] | '';
        assigned_to: number | '';
    };
    projects: SelectOption[];
    teamMembers: SelectOption[];
}>();

const filters = reactive({ ...props.filters });

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
        priority: '',
        status: '',
        assigned_to: '',
    });
    applyFilters();
};

const exportUrl = () => exportMethod({ query: { ...filters } }).url;

const formatDate = (value: string) =>
    new Intl.DateTimeFormat('nl-NL', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
</script>

<template>
    <Head title="Alle storingen" />

    <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4 sm:p-6">
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

        <Card>
            <CardContent class="p-4 sm:p-5">
                <form
                    class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(16rem,1fr)_11rem_9rem_10rem_11rem_auto]"
                    @submit.prevent="applyFilters"
                >
                    <div class="relative md:col-span-2 xl:col-span-1">
                        <Search
                            class="text-muted-foreground absolute top-3 left-3 size-4"
                        />
                        <Input
                            v-model="filters.search"
                            class="h-10 pl-9"
                            placeholder="Zoek op storing, omschrijving of project"
                        />
                    </div>
                    <select
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
                    <select
                        v-model="filters.priority"
                        class="border-input bg-background h-10 rounded-md border px-3 text-sm shadow-xs"
                    >
                        <option value="">Alle prioriteiten</option>
                        <option value="p1">P1 · Kritiek</option>
                        <option value="p2">P2 · Hoog</option>
                        <option value="p3">P3 · Normaal</option>
                        <option value="p4">P4 · Laag</option>
                    </select>
                    <select
                        v-model="filters.status"
                        class="border-input bg-background h-10 rounded-md border px-3 text-sm shadow-xs"
                    >
                        <option value="">Alle statussen</option>
                        <option value="open">Open</option>
                        <option value="handling">Afhandeling</option>
                        <option value="completed">Afgerond</option>
                    </select>
                    <select
                        v-model="filters.assigned_to"
                        class="border-input bg-background h-10 rounded-md border px-3 text-sm shadow-xs"
                    >
                        <option value="">Iedereen</option>
                        <option
                            v-for="teamMember in teamMembers"
                            :key="teamMember.id"
                            :value="teamMember.id"
                        >
                            {{ teamMember.name }}
                        </option>
                    </select>
                    <div class="flex gap-2">
                        <Button type="submit">Filteren</Button
                        ><Button
                            type="button"
                            variant="outline"
                            @click="clearFilters"
                            >Wis</Button
                        >
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card class="gap-0 overflow-hidden py-0">
            <CardContent v-if="issues.data.length" class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead class="text-muted-foreground bg-[#fcfdff]">
                            <tr>
                                <th class="px-6 py-4 font-medium">Storing</th>
                                <th class="px-6 py-4 font-medium">Project</th>
                                <th class="px-6 py-4 font-medium">
                                    Prioriteit
                                </th>
                                <th class="px-6 py-4 font-medium">Status</th>
                                <th class="px-6 py-4 font-medium">Gemeld op</th>
                                <th class="px-6 py-4 font-medium">
                                    Toegewezen aan
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
                                    class="max-w-sm px-6 py-4 font-medium text-[#101d3f]"
                                >
                                    {{ issue.title }}
                                </td>
                                <td class="px-6 py-4">{{ issue.project }}</td>
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
                                    {{ formatDate(issue.reported_at) }}
                                </td>
                                <td class="text-muted-foreground px-6 py-4">
                                    {{ issue.assigned_to ?? 'Niet toegewezen' }}
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
                        Pas je filters aan of meld een nieuwe storing.
                    </p>
                </div>
                <Button variant="outline" @click="clearFilters"
                    >Filters wissen</Button
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
