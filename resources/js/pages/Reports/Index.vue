<script setup lang="ts">
import type { IssuePriority } from '@/types/issues';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BarChart3,
    CalendarRange,
    CheckCircle2,
    Clock3,
    CircleAlert,
    Timer,
    TrendingDown,
    TrendingUp,
} from '@lucide/vue';
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
import { index as issuesIndex } from '@/routes/issues';
import { index } from '@/routes/reports';

type Report = {
    summary: {
        reported: number;
        completed: number;
        active: number;
        average_first_response_minutes: number | null;
        average_resolution_minutes: number | null;
    };
    comparison: {
        reported: number | null;
        completed: number | null;
        average_first_response_minutes: number | null;
        average_resolution_minutes: number | null;
    };
    sla: {
        response: SlaSummary;
        resolution: SlaSummary;
    };
    priorities: Array<{
        priority: IssuePriority;
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
    trend: {
        granularity: 'day' | 'week' | 'month';
        points: Array<{ label: string; reported: number }>;
    };
};

type SlaSummary = {
    tracked: number;
    met: number;
    breached: number;
    percentage: number | null;
};

const props = defineProps<{
    report: Report;
    filters: {
        period: 'week' | 'month' | 'year' | 'custom';
        from: string;
        to: string;
        project: number | '';
    };
    period: { label: string; from: string; to: string };
    projects: Array<{ id: number; name: string }>;
}>();

const filters = reactive({ ...props.filters });
const periods = [
    { key: 'week', label: 'Afgelopen week' },
    { key: 'month', label: 'Afgelopen maand' },
    { key: 'year', label: 'Afgelopen jaar' },
    { key: 'custom', label: 'Aangepast' },
] as const;
const slaCards = computed(() => [
    { name: 'Eerste reactie', data: props.report.sla.response },
    { name: 'Technische oplossing', data: props.report.sla.resolution },
]);

const applyFilters = () => {
    router.get(index.url(), filters, { preserveState: true, replace: true });
};

const selectPeriod = (period: (typeof periods)[number]['key']) => {
    filters.period = period;
    if (period !== 'custom') {
        filters.from = '';
        filters.to = '';
        applyFilters();
    }
};

const canApplyCustomPeriod = computed(
    () =>
        Boolean(filters.from) &&
        Boolean(filters.to) &&
        filters.to >= filters.from,
);

const applyProjectFilter = () => {
    if (filters.period !== 'custom' || canApplyCustomPeriod.value) {
        applyFilters();
    }
};

const duration = (minutes: number | null) => {
    if (minutes === null) return '—';
    if (minutes < 60) return `${minutes} min`;

    return `${Math.floor(minutes / 60)} u ${minutes % 60} min`;
};

const percentage = (value: number | null) =>
    value === null ? '—' : `${value}%`;

const comparison = (
    value: number | null,
    preference: 'increase' | 'decrease' | 'neutral' | true = 'neutral',
) => {
    if (value === null) return null;

    const normalizedPreference = preference === true ? 'decrease' : preference;

    return {
        icon: value >= 0 ? TrendingUp : TrendingDown,
        class:
            normalizedPreference === 'neutral' || value === 0
                ? 'text-muted-foreground'
                : (normalizedPreference === 'increase') === value > 0
                  ? 'text-emerald-700'
                  : 'text-rose-700',
        text: `${value > 0 ? '↑' : value < 0 ? '↓' : '→'} ${Math.abs(value)}% t.o.v. vorige periode`,
    };
};

const trendMaximum = computed(() =>
    Math.max(...props.report.trend.points.map((point) => point.reported), 1),
);

const reportedTrendPoints = computed(() =>
    props.report.trend.points.filter((point) => point.reported > 0),
);

const chartAxisLabel = (point: { label: string }) =>
    props.report.trend.granularity === 'day'
        ? point.label.split(' ')[0]
        : point.label;

const reportInsight = computed(() => {
    if (props.report.summary.active > 0) {
        return `${props.report.summary.active} ${props.report.summary.active === 1 ? 'storing vraagt' : 'storingen vragen'} nog opvolging.`;
    }

    if (props.report.summary.reported === 0) {
        return 'Er zijn geen storingen in deze periode geregistreerd.';
    }

    return 'Er staan geen openstaande storingen uit deze periode meer open.';
});

const slaPercentageClass = (value: number | null) => {
    if (value === null) return 'border-slate-200 bg-slate-50 text-slate-700';
    if (value < 80) return 'border-red-200 bg-red-50 text-red-700';
    if (value < 95) return 'border-orange-200 bg-orange-50 text-orange-700';

    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
};

const clearProjectFilter = () => {
    filters.project = '';
    applyFilters();
};

const projectIssuesUrl = (projectId: number) =>
    issuesIndex({
        query: {
            project: projectId,
            from: props.period.from,
            until: props.period.to,
        },
    });

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
            <h1 class="text-2xl font-semibold tracking-tight text-[#101d3f]">
                Rapportages
            </h1>
            <p class="text-muted-foreground">
                Inzicht in incidentvolume, doorlooptijden en SLA-prestaties.
            </p>
        </section>

        <Card class="gap-0 overflow-hidden py-0">
            <CardHeader class="border-b py-5 max-sm:grid-cols-1">
                <div>
                    <CardTitle class="text-lg">Rapportageperiode</CardTitle>
                    <CardDescription class="mt-1">
                        Kies de periode waarop alle inzichten zijn gebaseerd.
                    </CardDescription>
                </div>
                <div data-slot="card-action" class="grid gap-1 sm:min-w-52">
                    <Label for="project">Project</Label>
                    <select
                        id="project"
                        v-model="filters.project"
                        class="border-input bg-background h-10 rounded-md border px-3 text-sm"
                        @change="applyProjectFilter"
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
            </CardHeader>
            <CardContent class="space-y-4 p-4 sm:p-5">
                <div
                    class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div
                        class="bg-muted/30 inline-flex w-fit max-w-full flex-wrap gap-1 rounded-lg border p-1"
                        role="tablist"
                        aria-label="Kies rapportageperiode"
                    >
                        <Button
                            v-for="option in periods"
                            :key="option.key"
                            type="button"
                            size="sm"
                            :variant="
                                filters.period === option.key
                                    ? 'default'
                                    : 'ghost'
                            "
                            role="tab"
                            :aria-selected="filters.period === option.key"
                            @click="selectPeriod(option.key)"
                        >
                            {{ option.label }}
                        </Button>
                    </div>
                    <p
                        class="flex items-center gap-2 text-sm font-medium text-[#101d3f]"
                    >
                        <CalendarRange class="text-primary size-4" />
                        {{ period.label }}
                    </p>
                </div>

                <div
                    class="flex flex-wrap items-center gap-2 rounded-lg border border-blue-100 bg-blue-50/60 px-3 py-2 text-sm"
                >
                    <CircleAlert class="text-primary size-4 shrink-0" />
                    <span class="font-medium text-[#101d3f]">{{
                        reportInsight
                    }}</span>
                    <Button
                        v-if="filters.project"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="ml-auto"
                        @click="clearProjectFilter"
                        >Projectfilter wissen</Button
                    >
                </div>

                <div
                    v-if="filters.period === 'custom'"
                    class="bg-muted/20 grid gap-4 rounded-lg border p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                >
                    <div class="grid gap-1.5">
                        <Label for="from">Begindatum</Label>
                        <input
                            id="from"
                            v-model="filters.from"
                            type="date"
                            class="border-input bg-background h-10 rounded-md border px-3 text-sm"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="to">Einddatum</Label>
                        <input
                            id="to"
                            v-model="filters.to"
                            type="date"
                            :min="filters.from"
                            class="border-input bg-background h-10 rounded-md border px-3 text-sm"
                        />
                    </div>
                    <Button
                        type="button"
                        :disabled="!canApplyCustomPeriod"
                        @click="applyFilters"
                    >
                        Periode toepassen
                    </Button>
                </div>
                <p
                    v-if="filters.period === 'custom' && !canApplyCustomPeriod"
                    class="text-muted-foreground text-xs"
                >
                    Kies een geldige begin- en einddatum om de rapportage bij te
                    werken.
                </p>
            </CardContent>
        </Card>

        <section
            class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-5"
        >
            <Card
                ><CardContent class="flex items-center gap-4 p-5"
                    ><BarChart3 class="size-8 text-blue-600" />
                    <div>
                        <p class="text-muted-foreground text-sm">Gemeld</p>
                        <p
                            class="text-3xl font-semibold whitespace-nowrap tabular-nums"
                        >
                            {{ report.summary.reported }}
                        </p>
                        <p
                            v-if="comparison(report.comparison.reported)"
                            :class="[
                                'mt-1 flex items-center gap-1 text-xs',
                                comparison(report.comparison.reported)?.class,
                            ]"
                        >
                            <component
                                :is="
                                    comparison(report.comparison.reported)?.icon
                                "
                                class="size-3"
                            />
                            {{ comparison(report.comparison.reported)?.text }}
                        </p>
                        <p v-else class="text-muted-foreground mt-1 text-xs">
                            Geen vergelijkbare vorige periode
                        </p>
                    </div></CardContent
                ></Card
            >
            <Card
                ><CardContent class="flex items-center gap-4 p-5"
                    ><CheckCircle2 class="size-8 text-emerald-600" />
                    <div>
                        <p class="text-muted-foreground text-sm">Afgerond</p>
                        <p
                            class="text-3xl font-semibold whitespace-nowrap tabular-nums"
                        >
                            {{ report.summary.completed }}
                        </p>
                        <p
                            v-if="
                                comparison(
                                    report.comparison.completed,
                                    'increase',
                                )
                            "
                            :class="[
                                'mt-1 text-xs',
                                comparison(
                                    report.comparison.completed,
                                    'increase',
                                )?.class,
                            ]"
                        >
                            {{
                                comparison(
                                    report.comparison.completed,
                                    'increase',
                                )?.text
                            }}
                        </p>
                        <p v-else class="text-muted-foreground mt-1 text-xs">
                            Geen vergelijkbare vorige periode
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
                        <p
                            class="text-3xl font-semibold whitespace-nowrap tabular-nums"
                        >
                            {{
                                duration(
                                    report.summary
                                        .average_first_response_minutes,
                                )
                            }}
                        </p>
                        <p
                            v-if="
                                comparison(
                                    report.comparison
                                        .average_first_response_minutes,
                                    true,
                                )
                            "
                            :class="[
                                'mt-1 text-xs',
                                comparison(
                                    report.comparison
                                        .average_first_response_minutes,
                                    true,
                                )?.class,
                            ]"
                        >
                            {{
                                comparison(
                                    report.comparison
                                        .average_first_response_minutes,
                                    true,
                                )?.text
                            }}
                        </p>
                        <p v-else class="text-muted-foreground mt-1 text-xs">
                            Geen vergelijkbare vorige periode
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
                        <p
                            class="text-3xl font-semibold whitespace-nowrap tabular-nums"
                        >
                            {{
                                duration(
                                    report.summary.average_resolution_minutes,
                                )
                            }}
                        </p>
                        <p
                            v-if="
                                comparison(
                                    report.comparison
                                        .average_resolution_minutes,
                                    true,
                                )
                            "
                            :class="[
                                'mt-1 text-xs',
                                comparison(
                                    report.comparison
                                        .average_resolution_minutes,
                                    true,
                                )?.class,
                            ]"
                        >
                            {{
                                comparison(
                                    report.comparison
                                        .average_resolution_minutes,
                                    true,
                                )?.text
                            }}
                        </p>
                        <p v-else class="text-muted-foreground mt-1 text-xs">
                            Geen vergelijkbare vorige periode
                        </p>
                    </div></CardContent
                ></Card
            >
            <Card>
                <CardContent class="flex items-center gap-4 p-5">
                    <CircleAlert class="size-8 text-orange-500" />
                    <div>
                        <p class="text-muted-foreground text-sm">Nog open</p>
                        <p class="text-3xl font-semibold tabular-nums">
                            {{ report.summary.active }}
                        </p>
                        <p class="text-muted-foreground mt-1 text-xs">
                            Vraagt opvolging
                        </p>
                    </div>
                </CardContent>
            </Card>
        </section>

        <Card>
            <CardHeader>
                <CardTitle>Incidentontwikkeling</CardTitle>
                <CardDescription>
                    Gemelde storingen per
                    {{
                        report.trend.granularity === 'day'
                            ? 'dag'
                            : report.trend.granularity === 'week'
                              ? 'week'
                              : 'maand'
                    }}<template v-if="report.trend.granularity === 'day'"
                        >&nbsp;· elke markering is één kalenderdag</template
                    >.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="report.summary.reported"
                    class="flex h-48 items-end gap-1"
                    role="img"
                    aria-label="Grafiek met gemelde storingen per periode"
                >
                    <div
                        v-for="point in report.trend.points"
                        :key="point.label"
                        class="group flex h-full min-w-0 flex-1 flex-col justify-end text-center"
                        :aria-label="`${point.label}: ${point.reported} gemeld`"
                    >
                        <div class="relative flex flex-1 items-end">
                            <span
                                v-if="point.reported"
                                class="text-muted-foreground absolute -top-5 left-1/2 -translate-x-1/2 text-[10px] tabular-nums"
                                >{{ point.reported }}</span
                            >
                            <div
                                class="bg-primary/80 group-hover:bg-primary w-full rounded-t transition-colors"
                                :style="{
                                    height: `${Math.max((point.reported / trendMaximum) * 100, point.reported ? 4 : 0)}%`,
                                }"
                            />
                        </div>
                        <span
                            class="text-muted-foreground mt-2 h-3 text-center text-[10px] tabular-nums"
                            :aria-label="point.label"
                            >{{ chartAxisLabel(point) }}</span
                        >
                    </div>
                </div>
                <div
                    v-if="reportedTrendPoints.length"
                    class="mt-5 flex flex-wrap items-center gap-2 border-t pt-4 text-xs"
                >
                    <span class="text-muted-foreground font-medium"
                        >Dagen met incidenten</span
                    >
                    <span
                        v-for="point in reportedTrendPoints"
                        :key="`summary-${point.label}`"
                        class="border-border bg-muted/40 inline-flex items-center gap-1.5 rounded-md border px-2 py-1"
                    >
                        <span class="text-muted-foreground">{{
                            point.label
                        }}</span>
                        <span
                            class="text-foreground font-semibold tabular-nums"
                            >{{ point.reported }}</span
                        >
                    </span>
                </div>
                <p
                    v-if="report.summary.reported === 0"
                    class="text-muted-foreground mt-4 text-center text-sm"
                >
                    Geen storingen in deze periode.
                </p>
            </CardContent>
        </Card>

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
                            <template v-if="card.data.tracked">
                                {{ card.data.met }} van
                                {{ card.data.tracked }} binnen SLA ·
                                {{ card.data.breached }} te laat
                            </template>
                            <template v-else
                                >Geen vastgelegde SLA-momenten</template
                            >
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
                        class="grid grid-cols-[auto_1fr_auto] items-center gap-3 rounded-lg border p-3 text-sm sm:grid-cols-[auto_1fr_auto_auto]"
                    >
                        <IssuePriorityBadge :priority="item.priority" />
                        <span class="text-muted-foreground"
                            >{{ item.reported }} gemeld ·
                            {{ item.completed }} afgerond</span
                        >
                        <span class="text-muted-foreground hidden sm:inline"
                            >Gem. oplossing</span
                        >
                        <span class="font-medium tabular-nums">{{
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
                        <div
                            class="flex items-center justify-between gap-4 text-sm"
                        >
                            <span class="font-medium">{{ item.label }}</span>
                            <span class="text-muted-foreground"
                                >{{ item.count }} · {{ item.percentage }}%</span
                            >
                        </div>
                        <div
                            class="bg-muted mt-2 h-2 overflow-hidden rounded-full"
                        >
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
                                    <Link
                                        class="text-primary hover:underline"
                                        :href="projectIssuesUrl(project.id)"
                                    >
                                        {{ project.name }}
                                    </Link>
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
                                    <span
                                        class="inline-flex rounded-lg border px-2.5 py-1 text-xs font-medium"
                                        :class="
                                            slaPercentageClass(
                                                project.sla_percentage,
                                            )
                                        "
                                    >
                                        {{ percentage(project.sla_percentage) }}
                                    </span>
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
