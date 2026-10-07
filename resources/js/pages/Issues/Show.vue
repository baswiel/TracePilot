<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, CircleSlash, Clock3, Pencil, RotateCcw } from '@lucide/vue';
import { computed, ref } from 'vue';
import IssueDetails from '@/components/issues/IssueDetails.vue';
import IssueTimeline from '@/components/issues/IssueTimeline.vue';
import IssuePostmortemForm from '@/components/issues/IssuePostmortemForm.vue';
import IssueSlaDetails from '@/components/issues/IssueSlaDetails.vue';
import InputError from '@/components/InputError.vue';
import IssuePriorityBadge from '@/components/issues/IssuePriorityBadge.vue';
import IssueSlaBadge from '@/components/issues/IssueSlaBadge.vue';
import IssueStatusBadge from '@/components/issues/IssueStatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { update as markFirstResponse } from '@/routes/issues/first-response';
import { update as updateChecklistItem } from '@/routes/issues/checklist';
import { show as showProject } from '@/routes/projects';

import type { Issue, TeamMember } from '@/types/issues';
const props = defineProps<{ issue: Issue; teamMembers: TeamMember[] }>();
const isEditing = ref(false);
const updatingItemIds = ref<number[]>([]);
const resolutionItem = ref<Issue['checklist_items'][number] | null>(null);
const resolutionSummary = ref('');
const cause = ref<Issue['cause']>(null);
const postmortemRequired = ref(false);
const markingFirstResponse = ref(false);
const toggleItem = (item: Issue['checklist_items'][number]) => {
    if (updatingItemIds.value.includes(item.id)) {
        return;
    }

    if (item.marks_issue_resolved && !item.is_completed) {
        resolutionItem.value = item;

        return;
    }

    updateItem(item, !item.is_completed);
};

const updateItem = (
    item: Issue['checklist_items'][number],
    isCompleted: boolean,
    resolutionSummaryValue?: string,
    postmortemRequiredValue?: boolean,
    causeValue?: Issue['cause'],
) => {
    if (updatingItemIds.value.includes(item.id)) {
        return;
    }

    updatingItemIds.value = [...updatingItemIds.value, item.id];
    router.patch(
        updateChecklistItem([props.issue.id, item.id]).url,
        {
            is_completed: isCompleted,
            is_not_applicable: false,
            resolution_summary: resolutionSummaryValue,
            postmortem_required: postmortemRequiredValue,
            cause: causeValue,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                updatingItemIds.value = updatingItemIds.value.filter(
                    (id) => id !== item.id,
                );
            },
        },
    );
};

const completeResolutionItem = () => {
    if (!resolutionItem.value) {
        return;
    }

    const item = resolutionItem.value;
    updateItem(
        item,
        true,
        resolutionSummary.value,
        postmortemRequired.value,
        cause.value,
    );
    resolutionItem.value = null;
    resolutionSummary.value = '';
    cause.value = null;
    postmortemRequired.value = false;
};

const recordFirstResponse = () => {
    markingFirstResponse.value = true;
    router.patch(
        markFirstResponse(props.issue.id).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                markingFirstResponse.value = false;
            },
        },
    );
};

const markItemNotApplicable = (item: Issue['checklist_items'][number]) => {
    if (updatingItemIds.value.includes(item.id)) {
        return;
    }

    updatingItemIds.value = [...updatingItemIds.value, item.id];
    router.patch(
        updateChecklistItem([props.issue.id, item.id]).url,
        { is_completed: true, is_not_applicable: true },
        {
            preserveScroll: true,
            onFinish: () => {
                updatingItemIds.value = updatingItemIds.value.filter(
                    (id) => id !== item.id,
                );
            },
        },
    );
};

const checklistProgress = computed(() =>
    props.issue.checklist_progress.total
        ? Math.round(
              (props.issue.checklist_progress.completed /
                  props.issue.checklist_progress.total) *
                  100,
          )
        : 0,
);

const nextAction = computed(() => {
    if (!props.issue.first_responded_at) {
        return {
            title: 'Eerste reactie registreren',
            description: 'Leg vast dat het incident door het team is opgepakt.',
            tone: 'tone-danger',
        };
    }

    if (!props.issue.checklist_progress.all_required_completed) {
        return {
            title: 'Checklist afronden',
            description: `${props.issue.checklist_progress.required_total - props.issue.checklist_progress.required_completed} verplichte stappen staan nog open.`,
            tone: 'tone-warning',
        };
    }

    if (props.issue.postmortem_required && !props.issue.postmortem) {
        return {
            title: 'Postmortem vastleggen',
            description:
                'Leg oorzaak, impact en verbeteracties vast voordat je afsluit.',
            tone: 'tone-warning',
        };
    }

    return {
        title: 'Geen directe actie nodig',
        description: 'De verplichte incidentstappen zijn vastgelegd.',
        tone: 'tone-success',
    };
});

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});
</script>

<template>
    <Head :title="issue.title" />

    <div
        class="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-6 px-5 pt-7 pb-10 sm:px-8"
    >
        <section
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="min-w-0 space-y-3">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ issue.title }}
                </h1>
                <div class="flex flex-wrap items-center gap-2">
                    <IssueStatusBadge :status="issue.status" />
                    <IssuePriorityBadge :priority="issue.priority" />
                    <IssueSlaBadge
                        :response="issue.sla.response"
                        :resolution="issue.sla.resolution"
                    />
                    <span class="text-muted-foreground text-sm">
                        {{ issue.checklist_progress.required_completed }}/{{
                            issue.checklist_progress.required_total
                        }}
                        verplichte stappen
                    </span>
                </div>
                <p class="text-muted-foreground text-sm">
                    <Link
                        class="hover:text-foreground font-medium hover:underline"
                        :href="showProject(issue.project.id)"
                    >
                        {{ issue.project.name }}
                    </Link>
                    <template v-if="issue.project.customer_name">
                        · {{ issue.project.customer_name }}
                    </template>
                </p>
                <p class="text-muted-foreground text-sm">
                    Responders:
                    <span class="text-foreground font-medium">
                        {{
                            issue.project.first_responder?.name ||
                            'Niet toegewezen'
                        }}
                    </span>
                    <span aria-hidden="true"> · </span>
                    <span class="text-foreground font-medium">
                        {{
                            issue.project.second_responder?.name ||
                            'Niet toegewezen'
                        }}
                    </span>
                    <span aria-hidden="true"> · </span>
                    <span class="text-foreground font-medium">
                        {{
                            issue.project.third_responder?.name ||
                            'Niet toegewezen'
                        }}
                    </span>
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <Button
                    v-if="!isEditing"
                    size="sm"
                    variant="outline"
                    @click="isEditing = true"
                >
                    <Pencil /> Bewerken
                </Button>
            </div>
        </section>

        <section
            class="flex flex-col gap-2 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
            :class="nextAction.tone"
            aria-live="polite"
        >
            <div>
                <h2 class="text-foreground text-sm font-semibold">
                    Volgende stap: {{ nextAction.title }}
                </h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ nextAction.description }}
                </p>
            </div>
            <Button
                v-if="!issue.first_responded_at"
                :disabled="markingFirstResponse"
                @click="recordFirstResponse"
            >
                <Clock3 /> Eerste reactie registreren
            </Button>
        </section>

        <section
            class="bg-card grid overflow-hidden rounded-xl border sm:grid-cols-2 xl:grid-cols-4"
            aria-label="Kerngegevens storing"
        >
            <div
                class="border-b p-5 last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0"
            >
                <p class="text-muted-foreground text-sm">Gestart</p>
                <p class="mt-1 font-medium tabular-nums">
                    {{ issue.reported_at_label }}
                </p>
            </div>
            <div
                class="border-b p-5 last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0"
            >
                <p class="text-muted-foreground text-sm">Eerste reactie</p>
                <p class="mt-1 font-medium tabular-nums">
                    {{
                        issue.first_responded_at_label ??
                        'Nog niet geregistreerd'
                    }}
                </p>
            </div>
            <div
                class="border-b p-5 last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0"
            >
                <p class="text-muted-foreground text-sm">Technisch opgelost</p>
                <p class="mt-1 font-medium tabular-nums">
                    {{ issue.resolved_at_label ?? 'Nog niet opgelost' }}
                </p>
            </div>
            <div
                class="border-b p-5 last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0"
            >
                <p class="text-muted-foreground text-sm">Totale duur</p>
                <p class="mt-1 font-medium tabular-nums">
                    {{ issue.elapsed_duration }}
                </p>
            </div>
        </section>

        <section class="grid items-start gap-6 lg:grid-cols-3">
            <IssueDetails
                v-model:editing="isEditing"
                :issue="issue"
                :team-members="teamMembers"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Checklistvoortgang</CardTitle>
                    <CardDescription>
                        {{ issue.checklist_progress.completed }} van
                        {{ issue.checklist_progress.total }} afgevinkt ·
                        {{ checklistProgress }}%
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <div
                        class="bg-muted h-2 overflow-hidden rounded-full"
                        aria-hidden="true"
                    >
                        <div
                            class="bg-primary h-full rounded-full transition-all"
                            :style="{ width: `${checklistProgress}%` }"
                        />
                    </div>
                    <p class="text-muted-foreground">
                        {{ issue.checklist_progress.required_completed }} van
                        {{ issue.checklist_progress.required_total }} verplichte
                        stappen klaar.
                    </p>
                    <p
                        class="font-medium"
                        :class="
                            issue.checklist_progress.all_required_completed
                                ? 'text-[var(--tracepilot-success-ink)]'
                                : 'text-[var(--tracepilot-warning-ink)]'
                        "
                    >
                        {{
                            issue.checklist_progress.all_required_completed
                                ? 'Alle verplichte stappen zijn klaar.'
                                : 'Er zijn nog verplichte stappen open.'
                        }}
                    </p>
                </CardContent>
            </Card>
        </section>

        <IssueSlaDetails :sla="issue.sla" />

        <IssuePostmortemForm
            v-if="issue.postmortem_required"
            :issue-id="issue.id"
            :postmortem="issue.postmortem"
            :team-members="teamMembers"
        />

        <Dialog
            :open="resolutionItem !== null"
            @update:open="(isOpen) => !isOpen && (resolutionItem = null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Issue als opgelost markeren</DialogTitle>
                    <DialogDescription>
                        Beschrijf kort wat is opgelost. Deze notitie helpt bij
                        het herkennen van terugkerende problemen.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="resolution_summary">Oplossing</Label>
                    <textarea
                        id="resolution_summary"
                        v-model="resolutionSummary"
                        rows="4"
                        maxlength="2000"
                        placeholder="Bijvoorbeeld: cache geleegd en de foutieve configuratie hersteld."
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="cause">Type storing</Label>
                    <select
                        id="cause"
                        v-model="cause"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    >
                        <option :value="null">Nog niet geclassificeerd</option>
                        <option value="internal_knowledge_gap">
                            Kennis ontbreekt intern
                        </option>
                        <option value="customer_knowledge_gap">
                            Kennis ontbreekt bij klant
                        </option>
                        <option value="user_error">Gebruikersfout</option>
                        <option value="code_defect">Codebug</option>
                        <option value="configuration_error">
                            Configuratiefout
                        </option>
                        <option value="infrastructure">Infrastructuur</option>
                        <option value="external_dependency">
                            Externe afhankelijkheid
                        </option>
                        <option value="other">Anders</option>
                    </select>
                    <p class="text-muted-foreground text-sm">
                        Gebruik dit voor trendanalyse en verbeteracties, zoals
                        kennisdeling of strengere code reviews.
                    </p>
                </div>
                <label
                    class="flex items-start gap-3 rounded-lg border p-3 text-sm"
                >
                    <input
                        v-model="postmortemRequired"
                        class="accent-primary mt-0.5 size-4"
                        type="checkbox"
                    />
                    <span>
                        <span class="font-medium">Postmortem nodig</span>
                        <span class="text-muted-foreground mt-0.5 block">
                            Laat dit uit als een postmortem niet nodig is. De
                            postmortemstap in de checklist wordt dan als niet
                            van toepassing afgerond.
                        </span>
                    </span>
                </label>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="resolutionItem = null"
                    >
                        Annuleren
                    </Button>
                    <Button type="button" @click="completeResolutionItem">
                        Als opgelost markeren
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <section class="grid items-start gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Checklist</CardTitle>
                    <CardDescription>
                        Voltooi stappen zodra ze zijn uitgevoerd. Optionele
                        stappen blokkeren afronding niet.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ul
                        v-if="issue.checklist_items.length"
                        class="divide-y rounded-lg border"
                    >
                        <li
                            v-for="item in issue.checklist_items"
                            :key="item.id"
                            class="flex items-center gap-3 p-4"
                        >
                            <span
                                aria-hidden="true"
                                class="border-primary flex size-6 shrink-0 items-center justify-center rounded border transition-colors disabled:opacity-50"
                                :class="
                                    item.is_completed
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-background'
                                "
                                @click="toggleItem(item)"
                            >
                                <Check
                                    v-if="item.is_completed"
                                    class="size-4"
                                />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p
                                    :class="{
                                        'text-muted-foreground line-through':
                                            item.is_completed,
                                    }"
                                    class="text-sm font-medium"
                                >
                                    {{ item.name }}
                                </p>
                                <p class="text-muted-foreground mt-1 text-xs">
                                    <span>{{
                                        item.is_required
                                            ? 'Verplicht'
                                            : 'Optioneel'
                                    }}</span>
                                    <span v-if="item.marks_issue_resolved">
                                        · Markeert storing als opgelost</span
                                    >
                                    <span v-if="item.is_not_applicable">
                                        · Niet van toepassing</span
                                    >
                                    <span v-if="item.completed_at">
                                        · Afgevinkt {{ item.completed_at
                                        }}{{
                                            item.completed_by
                                                ? ` door ${item.completed_by}`
                                                : ''
                                        }}</span
                                    >
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <Button
                                    v-if="!item.is_completed"
                                    size="sm"
                                    variant="ghost"
                                    :disabled="
                                        updatingItemIds.includes(item.id)
                                    "
                                    @click="markItemNotApplicable(item)"
                                >
                                    <CircleSlash /> Niet van toepassing
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    :disabled="
                                        updatingItemIds.includes(item.id)
                                    "
                                    @click="toggleItem(item)"
                                >
                                    <RotateCcw v-if="item.is_completed" />
                                    {{
                                        item.is_completed
                                            ? 'Heropenen'
                                            : 'Voltooien'
                                    }}
                                </Button>
                            </div>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="text-muted-foreground rounded-lg border border-dashed p-5 text-sm"
                    >
                        Er zijn geen checklistitems aan deze storing gekoppeld.
                    </p>
                </CardContent>
            </Card>

            <IssueTimeline
                :issue-id="issue.id"
                :activities="issue.activities"
                :team-members="teamMembers"
            />
        </section>
    </div>
</template>
