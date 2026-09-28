<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { BarChart3, CheckCircle2, Clock3, Timer } from '@lucide/vue';
import { computed, reactive } from 'vue';
import IssuePriorityBadge from '@/components/issues/IssuePriorityBadge.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { index } from '@/routes/reports';

type Report = {
    summary: {
        reported: number;
        completed: number;
        active: number;
        average_first_response_minutes: number | null;
        average_resolution_minutes: number | null;
    };
    sla: {
        response: SlaSummary;
        resolution: SlaSummary;
    };
    priorities: Array<{
        priority: 'p1' | 'p2' | 'p3' | 'p4';
        reported: number;
        completed: number;
        average_resolution_minutes: number | null;
    }>;
    causes: Array<{
        cause: string;
        label: string;
        count: number;
        percentage: number;
    }>;
    projects: Array<{
        id: number;
        name: string;
        reported: number;
        completed: number;
        average_resolution_minutes: number | null;
        sla_percentage: number | null;
    }>;
};

type SlaSummary = {
    tracked: number;
    met: number;
    breached: number;
    percentage: number | null;
};

const props = defineProps<{
    report: Report;
    filters: { from: string; until: string; project: number | '' };
    projects: Array<{ id: number; name: string }>;
}>();

const filters = reactive({ ...props.filters });
const slaCards = computed(() => [
    { name: 'Eerste reactie', data: props.report.sla.response },
    { name: 'Technische oplossing', data: props.report.sla.resolution },
]);

const applyFilters = () => {
    router.get(index.url(), filters, { preserveState: true, replace: true });
};

const duration = (minutes: number | null) => {
    if (minutes === null) return '—';
    if (minutes < 60) return `${minutes} min`;

    return `${Math.floor(minutes / 60)} u ${minutes % 60} min`;
};

const percentage = (value: number | null) =>
    value === null ? '—' : `${value}%`;

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Rapportages', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Rapportages" />

    <div
        class="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-6 px-5 pt-2 pb-10 sm:px-8"
    >
        <section class="space-y-1 pt-1">
            <h1
                class="text-[clamp(1.875rem,3vw,2.5rem)] font-semibold tracking-[-0.035em] text-[#101d3f]"
            >
                Rapportages
            </h1>
            <p class="text-muted-foreground">
                Inzicht in incidentvolume, doorlooptijden en SLA-prestaties.
            </p>
        </section>

        <Card>
            <CardContent class="grid gap-4 p-5 sm:grid-cols-3">
                <div class="grid gap-1">
                    <Label for="from">Van</Label>
                    <input
                        id="from"
                        v-model="filters.from"
                        type="date"
                        class="border-input bg-background h-10 rounded-md border px-3 text-sm"
                        @change="applyFilters"
                    />
                </div>
                <div class="grid gap-1">
                    <Label for="until">Tot en met</Label>
                    <input
                        id="until"
                        v-model="filters.until"
                        type="date"
                        class="border-input bg-background h-10 rounded-md border px-3 text-sm"
                        @change="applyFilters"
                    />
                </div>
                <div class="grid gap-1">
                    <Label for="project">Project</Label>
                    <select
                        id="project"
                        v-model="filters.project"
                        class="border-input bg-background h-10 rounded-md border px-3 text-sm"
                        @change="applyFilters"
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
            </CardContent>
        </Card>

        <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <Card
                ><CardContent class="flex items-center gap-4 p-5"
                    ><BarChart3 class="size-8 text-blue-600" />
                    <div>
                        <p class="text-muted-foreground text-sm">Gemeld</p>
                        <p class="text-3xl font-semibold">
                            {{ report.summary.reported }}
                        </p>
                    </div></CardContent
                ></Card
            >
            <Card
                ><CardContent class="flex items-center gap-4 p-5"
                    ><CheckCircle2 class="size-8 text-emerald-600" />
                    <div>
                        <p class="text-muted-foreground text-sm">Afgerond</p>
                        <p class="text-3xl font-semibold">
                            {{ report.summary.completed }}
                        </p>
                    </div></CardContent
                ></Card
            >
            <Card
                ><CardContent class="flex items-center gap-4 p-5"
                    ><Clock3 class="size-8 text-orange-500" />
                    <div>
                        <p class="text-muted-foreground text-sm">
                            Gem. eerste reactie
                        </p>
                        <p class="text-3xl font-semibold">
                            {{
                                duration(
                                    report.summary
                                        .average_first_response_minutes,
                                )
                            }}
                        </p>
                    </div></CardContent
                ></Card
            >
            <Card
                ><CardContent class="flex items-center gap-4 p-5"
                    ><Timer class="size-8 text-violet-600" />
                    <div>
                        <p class="text-muted-foreground text-sm">
                            Gem. oplossing
                        </p>
                        <p class="text-3xl font-semibold">
                            {{
                                duration(
                                    report.summary.average_resolution_minutes,
                                )
                            }}
                        </p>
                    </div></CardContent
                ></Card
            >
        </section>

        <section class="grid gap-5 lg:grid-cols-2">
            <Card>
                <CardHeader
                    ><CardTitle>SLA-prestaties</CardTitle
                    ><CardDescription
                        >Alleen vastgelegde reacties en technische oplossingen
                        met een SLA-target.</CardDescription
                    ></CardHeader
                >
                <CardContent class="grid gap-4 sm:grid-cols-2">
                    <div
                        v-for="card in slaCards"
                        :key="card.name"
                        class="rounded-lg border p-4"
                    >
                        <p class="font-medium">{{ card.name }}</p>
                        <p class="mt-2 text-3xl font-semibold">
                            {{ percentage(card.data.percentage) }}
                        </p>
                        <p class="text-muted-foreground mt-1 text-sm">
                            {{ card.data.met }} binnen SLA ·
                            {{ card.data.breached }} te laat
                        </p>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader
                    ><CardTitle>Verdeling per prioriteit</CardTitle
                    ><CardDescription
                        >Gemelde en afgeronde storingen in de gekozen
                        periode.</CardDescription
                    ></CardHeader
                >
                <CardContent class="space-y-3">
                    <div
                        v-for="item in report.priorities"
                        :key="item.priority"
                        class="flex items-center justify-between rounded-lg border p-3 text-sm"
                    >
                        <IssuePriorityBadge :priority="item.priority" /><span
                            >{{ item.reported }} gemeld</span
                        ><span>{{ item.completed }} afgerond</span
                        ><span class="text-muted-foreground">{{
                            duration(item.average_resolution_minutes)
                        }}</span>
                    </div>
                </CardContent>
            </Card>
        </section>

        <Card>
            <CardHeader
                ><CardTitle>Trends naar type storing</CardTitle
                ><CardDescription
                    >Alleen storingen die bij de afhandeling zijn
                    geclassificeerd.</CardDescription
                ></CardHeader
            >
            <CardContent>
                <div v-if="report.causes.length" class="space-y-3">
                    <div v-for="item in report.causes" :key="item.cause">
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span class="font-medium">{{ item.label }}</span>
                            <span class="text-muted-foreground"
                                >{{ item.count }} · {{ item.percentage }}%</span
                            >
                        </div>
                        <div class="bg-muted mt-2 h-2 overflow-hidden rounded-full">
                            <div
                                class="bg-primary h-full rounded-full"
                                :style="{ width: `${item.percentage}%` }"
                            />
                        </div>
                    </div>
                </div>
                <p v-else class="text-muted-foreground text-sm">
                    Nog geen geclassificeerde storingen in deze periode.
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                ><CardTitle>Projectprestaties</CardTitle
                ><CardDescription
                    >Oplostijd en SLA-score per project.</CardDescription
                ></CardHeader
            >
            <CardContent class="p-0">
                <div v-if="report.projects.length" class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left text-sm">
                        <thead class="text-muted-foreground bg-muted/40">
                            <tr>
                                <th class="px-6 py-3 font-medium">Project</th>
                                <th class="px-6 py-3 font-medium">Gemeld</th>
                                <th class="px-6 py-3 font-medium">Afgerond</th>
                                <th class="px-6 py-3 font-medium">
                                    Gem. oplossing
                                </th>
                                <th class="px-6 py-3 font-medium">
                                    SLA oplossing
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="project in report.projects"
                                :key="project.id"
                            >
                                <td class="px-6 py-4 font-medium">
                                    {{ project.name }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ project.reported }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ project.completed }}
                                </td>
                                <td class="px-6 py-4">
                                    {{
                                        duration(
                                            project.average_resolution_minutes,
                                        )
                                    }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ percentage(project.sla_percentage) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p
                    v-else
                    class="text-muted-foreground px-6 py-12 text-center text-sm"
                >
                    Geen storingen in deze periode.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
