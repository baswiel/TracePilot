<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    CircleSlash,
    Paperclip,
    Pencil,
    Plus,
    RotateCcw,
    Send,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import IssuePriorityBadge from '@/components/issues/IssuePriorityBadge.vue';
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
import { update as updatePostmortem } from '@/routes/issues/postmortem';
import { store as storeTimelineEntry } from '@/routes/issues/timeline';
import { update } from '@/routes/issues';
import { show as showProject } from '@/routes/projects';

type Issue = {
    id: number;
    title: string;
    description: string | null;
    priority: 'p1' | 'p2' | 'p3' | 'p4';
    status: 'open' | 'handling' | 'completed';
    reported_at: string;
    reported_at_label: string;
    first_responded_at: string | null;
    elapsed_duration: string;
    resolved_at: string | null;
    resolution_summary: string | null;
    cause:
        | 'internal_knowledge_gap'
        | 'customer_knowledge_gap'
        | 'user_error'
        | 'code_defect'
        | 'configuration_error'
        | 'infrastructure'
        | 'external_dependency'
        | 'other'
        | null;
    postmortem_required: boolean | null;
    knowledge_base_recorded: boolean;
    is_trend: boolean;
    completed_at: string | null;
    project: {
        id: number;
        name: string;
        customer_name: string | null;
        first_responder: {
            id: number;
            name: string;
            email: string | null;
        } | null;
        second_responder: {
            id: number;
            name: string;
            email: string | null;
        } | null;
        third_responder: {
            id: number;
            name: string;
            email: string | null;
        } | null;
    };
    team_member_id: number | null;
    assigned_to_name: string | null;
    checklist_items: Array<{
        id: number;
        name: string;
        is_required: boolean;
        marks_issue_resolved: boolean;
        is_completed: boolean;
        is_not_applicable: boolean;
        completed_at: string | null;
        completed_by: string | null;
    }>;
    checklist_progress: {
        completed: number;
        total: number;
        required_completed: number;
        required_total: number;
        all_required_completed: boolean;
    };
    sla: {
        response: SlaMilestone;
        resolution: SlaMilestone;
        needs_attention: boolean;
    };
    postmortem: Postmortem | null;
    activities: Array<{
        id: number;
        action: string;
        description: string;
        created_at: string;
        user: string | null;
        mentions: Array<{ id: number; name: string }>;
        attachment: { name: string; download_url: string } | null;
    }>;
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

type TeamMember = { id: number; name: string; email: string | null };

type PostmortemActionItem = {
    id: number | null;
    title: string;
    owner_team_member_id: number | null;
    owner_name?: string | null;
    due_date: string;
    is_completed: boolean;
};

type Postmortem = {
    root_cause: string;
    impact: string;
    action_items: PostmortemActionItem[];
};

const props = defineProps<{ issue: Issue; teamMembers: TeamMember[] }>();
const isEditing = ref(false);
const updatingItemIds = ref<number[]>([]);
const resolutionItem = ref<Issue['checklist_items'][number] | null>(null);
const resolutionSummary = ref('');
const cause = ref<Issue['cause']>(null);
const postmortemRequired = ref(false);
const markingFirstResponse = ref(false);
const dateTimeForInput = (value: string | null) =>
    value ? value.replace(' ', 'T').slice(0, 16) : '';
const detailsForm = useForm({
    title: props.issue.title,
    description: props.issue.description ?? '',
    priority: props.issue.priority,
    team_member_id: props.issue.team_member_id ?? '',
    knowledge_base_recorded: props.issue.knowledge_base_recorded,
    is_trend: props.issue.is_trend,
    reported_at: dateTimeForInput(props.issue.reported_at),
    first_responded_at: dateTimeForInput(props.issue.first_responded_at),
    resolved_at: dateTimeForInput(props.issue.resolved_at),
});
const timelineForm = useForm({
    type: 'comment' as 'comment' | 'decision',
    body: '',
    mention_ids: [] as number[],
    attachment: null as File | null,
});
const postmortemForm = useForm({
    root_cause: props.issue.postmortem?.root_cause ?? '',
    impact: props.issue.postmortem?.impact ?? '',
    action_items: (props.issue.postmortem?.action_items ?? []).map((item) => ({
        id: item.id,
        title: item.title,
        owner_team_member_id: item.owner_team_member_id,
        due_date: item.due_date ?? '',
        is_completed: item.is_completed,
    })),
});

const submitDetails = () => {
    detailsForm.put(update(props.issue.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
        },
    });
};

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

const toggleMention = (teamMemberId: number) => {
    timelineForm.mention_ids = timelineForm.mention_ids.includes(teamMemberId)
        ? timelineForm.mention_ids.filter((id) => id !== teamMemberId)
        : [...timelineForm.mention_ids, teamMemberId];
};

const selectAttachment = (event: Event) => {
    const input = event.target as HTMLInputElement;
    timelineForm.attachment = input.files?.[0] ?? null;
};

const submitTimelineEntry = () => {
    timelineForm.post(storeTimelineEntry(props.issue.id).url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            timelineForm.reset();
            timelineForm.type = 'comment';
        },
    });
};

const addPostmortemActionItem = () => {
    postmortemForm.action_items.push({
        id: null,
        title: '',
        owner_team_member_id: null,
        due_date: '',
        is_completed: false,
    });
};

const removePostmortemActionItem = (index: number) => {
    postmortemForm.action_items.splice(index, 1);
};

const submitPostmortem = () => {
    postmortemForm.put(updatePostmortem(props.issue.id).url, {
        preserveScroll: true,
    });
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

const formatDate = (value: string) =>
    new Intl.DateTimeFormat('nl-NL', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));

const slaClass = (state: SlaMilestone['state']) =>
    ({
        unavailable: 'border-slate-200 bg-slate-50 text-slate-700',
        on_track: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        at_risk: 'border-orange-200 bg-orange-50 text-orange-700',
        overdue: 'border-red-200 bg-red-50 text-red-700',
        met: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        breached: 'border-red-200 bg-red-50 text-red-700',
    })[state];

const deadlineLabel = (milestone: SlaMilestone) =>
    milestone.deadline_at
        ? formatDate(milestone.deadline_at)
        : 'Niet ingesteld';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});
</script>

<template>
    <Head :title="issue.title" />

    <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4 sm:p-6">
        <section
            class="bg-card flex flex-col gap-4 rounded-xl border p-5 shadow-sm sm:flex-row sm:items-start sm:justify-between sm:p-6"
        >
            <div class="min-w-0 space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ issue.title }}
                    </h1>
                    <IssueStatusBadge :status="issue.status" />
                    <IssuePriorityBadge :priority="issue.priority" />
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
        </section>

        <section class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader class="flex-row items-start justify-between gap-4">
                    <div>
                        <CardTitle>Issuegegevens</CardTitle>
                        <CardDescription>
                            Details en verantwoordelijke van deze storing.
                        </CardDescription>
                    </div>
                    <Button
                        v-if="!isEditing"
                        size="sm"
                        variant="outline"
                        @click="isEditing = true"
                    >
                        <Pencil /> Bewerken
                    </Button>
                </CardHeader>
                <CardContent>
                    <form
                        v-if="isEditing"
                        class="space-y-5"
                        @submit.prevent="submitDetails"
                    >
                        <div class="grid gap-2">
                            <Label for="title">Titel</Label>
                            <Input
                                id="title"
                                v-model="detailsForm.title"
                                required
                            />
                            <InputError :message="detailsForm.errors.title" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="description">Omschrijving</Label>
                            <textarea
                                id="description"
                                v-model="detailsForm.description"
                                rows="5"
                                class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            />
                            <InputError
                                :message="detailsForm.errors.description"
                            />
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="priority">Prioriteit</Label>
                                <select
                                    id="priority"
                                    v-model="detailsForm.priority"
                                    class="border-input bg-background ring-offset-background focus-visible:ring-ring h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                                >
                                    <option value="p1">P1 — kritiek</option>
                                    <option value="p2">P2 — hoog</option>
                                    <option value="p3">P3 — normaal</option>
                                    <option value="p4">P4 — laag</option>
                                </select>
                                <InputError
                                    :message="detailsForm.errors.priority"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="team_member_id"
                                    >Verantwoordelijke</Label
                                >
                                <select
                                    id="team_member_id"
                                    v-model="detailsForm.team_member_id"
                                    class="border-input bg-background ring-offset-background focus-visible:ring-ring h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                                >
                                    <option value="">
                                        Nog niet toegewezen
                                    </option>
                                    <option
                                        v-for="teamMember in teamMembers"
                                        :key="teamMember.id"
                                        :value="teamMember.id"
                                    >
                                        {{ teamMember.name }}
                                    </option>
                                </select>
                                <InputError
                                    :message="detailsForm.errors.team_member_id"
                                />
                            </div>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="reported_at">Gestart op</Label>
                                <Input id="reported_at" v-model="detailsForm.reported_at" required type="datetime-local" />
                                <InputError :message="detailsForm.errors.reported_at" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="first_responded_at">First response op</Label>
                                <Input id="first_responded_at" v-model="detailsForm.first_responded_at" type="datetime-local" />
                                <InputError :message="detailsForm.errors.first_responded_at" />
                            </div>
                            <div class="grid gap-2 sm:col-span-2">
                                <Label for="resolved_at">Technisch opgelost op</Label>
                                <Input id="resolved_at" v-model="detailsForm.resolved_at" type="datetime-local" />
                                <InputError :message="detailsForm.errors.resolved_at" />
                            </div>
                        </div>
                        <div class="space-y-3 rounded-lg border p-4">
                            <label class="flex items-center gap-3 text-sm">
                                <input
                                    v-model="
                                        detailsForm.knowledge_base_recorded
                                    "
                                    class="accent-primary size-4"
                                    type="checkbox"
                                />
                                <span>Uitkomst opgenomen in kennisbank</span>
                            </label>
                            <label class="flex items-center gap-3 text-sm">
                                <input
                                    v-model="detailsForm.is_trend"
                                    class="accent-primary size-4"
                                    type="checkbox"
                                />
                                <span>Onderdeel van een trend</span>
                            </label>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <Button
                                :disabled="detailsForm.processing"
                                type="submit"
                            >
                                Opslaan
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="isEditing = false"
                            >
                                Annuleren
                            </Button>
                        </div>
                    </form>

                    <dl v-else class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <dt class="text-muted-foreground text-sm">
                                Omschrijving
                            </dt>
                            <dd class="mt-1 text-sm whitespace-pre-line">
                                {{
                                    issue.description ||
                                    'Geen omschrijving opgegeven.'
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground text-sm">
                                Verantwoordelijke
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{
                                    issue.assigned_to_name || 'Niet toegewezen'
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground text-sm">
                                Gemeld
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ issue.reported_at_label }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground text-sm">
                                Verstreken duur
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ issue.elapsed_duration }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground text-sm">
                                Eerste reactie
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{
                                    issue.first_responded_at ||
                                    'Nog niet vastgelegd'
                                }}
                            </dd>
                        </div>
                        <div v-if="issue.resolved_at">
                            <dt class="text-muted-foreground text-sm">
                                Technisch opgelost
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ issue.resolved_at }}
                            </dd>
                        </div>
                        <div
                            v-if="issue.resolution_summary"
                            class="sm:col-span-2"
                        >
                            <dt class="text-muted-foreground text-sm">
                                Oplossing
                            </dt>
                            <dd class="mt-1 text-sm whitespace-pre-line">
                                {{ issue.resolution_summary }}
                            </dd>
                        </div>
                        <div v-if="issue.cause">
                            <dt class="text-muted-foreground text-sm">
                                Type storing
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{
                                    {
                                        internal_knowledge_gap:
                                            'Kennis ontbreekt intern',
                                        customer_knowledge_gap:
                                            'Kennis ontbreekt bij klant',
                                        user_error: 'Gebruikersfout',
                                        code_defect: 'Codebug',
                                        configuration_error: 'Configuratiefout',
                                        infrastructure: 'Infrastructuur',
                                        external_dependency:
                                            'Externe afhankelijkheid',
                                        other: 'Anders',
                                    }[issue.cause]
                                }}
                            </dd>
                        </div>
                        <div v-if="issue.completed_at">
                            <dt class="text-muted-foreground text-sm">
                                Afgerond
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ issue.completed_at }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground text-sm">
                                Kennisbank
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{
                                    issue.knowledge_base_recorded
                                        ? 'Opgenomen'
                                        : 'Niet opgenomen'
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground text-sm">Trend</dt>
                            <dd class="mt-1 text-sm">
                                {{ issue.is_trend ? 'Ja' : 'Nee' }}
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Checklistvoortgang</CardTitle>
                    <CardDescription>
                        {{ issue.checklist_progress.completed }} van
                        {{ issue.checklist_progress.total }} afgevinkt
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <div
                        class="bg-muted h-2 overflow-hidden rounded-full"
                        aria-hidden="true"
                    >
                        <div
                            class="bg-primary h-full rounded-full transition-all"
                            :style="{
                                width: `${issue.checklist_progress.total ? (issue.checklist_progress.completed / issue.checklist_progress.total) * 100 : 0}%`,
                            }"
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
                                ? 'text-green-700'
                                : 'text-orange-700'
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

        <Card>
            <CardHeader>
                <CardTitle>SLA-bewaking</CardTitle>
                <CardDescription>
                    Deadlines worden berekend vanaf het moment van melden op
                    basis van de prioriteit.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <section
                    v-for="milestone in [
                        issue.sla.response,
                        issue.sla.resolution,
                    ]"
                    :key="milestone.label"
                    class="rounded-lg border p-4"
                    :class="slaClass(milestone.state)"
                >
                    <p class="font-medium">{{ milestone.label }}</p>
                    <p class="mt-1 text-sm">
                        Deadline: {{ deadlineLabel(milestone) }}
                    </p>
                    <p v-if="milestone.target_minutes" class="mt-1 text-sm">
                        Termijn: {{ milestone.target_minutes }} minuten
                    </p>
                </section>
            </CardContent>
            <CardContent v-if="!issue.first_responded_at" class="pt-0">
                <Button
                    :disabled="markingFirstResponse"
                    @click="recordFirstResponse"
                >
                    Eerste reactie vastleggen
                </Button>
            </CardContent>
        </Card>

        <Card v-if="issue.postmortem_required">
            <CardHeader>
                <CardTitle>Postmortem</CardTitle>
                <CardDescription>
                    Leg de oorzaak, impact en verbeteracties vast om herhaling
                    te voorkomen.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-6" @submit.prevent="submitPostmortem">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="postmortem_root_cause">Oorzaak</Label>
                            <textarea
                                id="postmortem_root_cause"
                                v-model="postmortemForm.root_cause"
                                rows="5"
                                maxlength="5000"
                                placeholder="Wat was de onderliggende oorzaak van deze storing?"
                                class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            />
                            <InputError
                                :message="postmortemForm.errors.root_cause"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="postmortem_impact">Impact</Label>
                            <textarea
                                id="postmortem_impact"
                                v-model="postmortemForm.impact"
                                rows="5"
                                maxlength="5000"
                                placeholder="Welke klanten, systemen of processen zijn geraakt?"
                                class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            />
                            <InputError
                                :message="postmortemForm.errors.impact"
                            />
                        </div>
                    </div>

                    <section class="space-y-3">
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div>
                                <h3 class="text-sm font-medium">Actiepunten</h3>
                                <p class="text-muted-foreground text-sm">
                                    Maak verbetering concreet met een eigenaar
                                    en deadline.
                                </p>
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="addPostmortemActionItem"
                            >
                                <Plus /> Actiepunt toevoegen
                            </Button>
                        </div>
                        <div
                            v-if="postmortemForm.action_items.length"
                            class="space-y-3"
                        >
                            <div
                                v-for="(
                                    actionItem, index
                                ) in postmortemForm.action_items"
                                :key="actionItem.id ?? `new-${index}`"
                                class="grid gap-3 rounded-lg border p-4 lg:grid-cols-[minmax(0,1fr)_12rem_10rem_auto] lg:items-end"
                            >
                                <div class="grid gap-2">
                                    <Label :for="`postmortem_action_${index}`"
                                        >Actiepunt</Label
                                    >
                                    <Input
                                        :id="`postmortem_action_${index}`"
                                        v-model="actionItem.title"
                                        maxlength="255"
                                        placeholder="Bijvoorbeeld: voeg een monitoringcheck toe"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label :for="`postmortem_owner_${index}`"
                                        >Eigenaar</Label
                                    >
                                    <select
                                        :id="`postmortem_owner_${index}`"
                                        v-model="
                                            actionItem.owner_team_member_id
                                        "
                                        class="border-input bg-background ring-offset-background focus-visible:ring-ring h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                                    >
                                        <option :value="null">
                                            Niet toegewezen
                                        </option>
                                        <option
                                            v-for="teamMember in teamMembers"
                                            :key="teamMember.id"
                                            :value="teamMember.id"
                                        >
                                            {{ teamMember.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <Label :for="`postmortem_due_${index}`"
                                        >Deadline</Label
                                    >
                                    <Input
                                        :id="`postmortem_due_${index}`"
                                        v-model="actionItem.due_date"
                                        type="date"
                                    />
                                </div>
                                <div class="flex items-center gap-2 pb-1">
                                    <label
                                        class="flex items-center gap-2 text-sm"
                                    >
                                        <input
                                            v-model="actionItem.is_completed"
                                            class="accent-primary size-4"
                                            type="checkbox"
                                        />
                                        Klaar
                                    </label>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        :aria-label="`Verwijder actiepunt ${index + 1}`"
                                        @click="
                                            removePostmortemActionItem(index)
                                        "
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </div>
                        </div>
                        <p
                            v-else
                            class="text-muted-foreground rounded-lg border border-dashed p-4 text-sm"
                        >
                            Nog geen actiepunten. Voeg verbeterwerk toe om
                            herhaling te voorkomen.
                        </p>
                    </section>

                    <Button :disabled="postmortemForm.processing" type="submit">
                        Postmortem opslaan
                    </Button>
                </form>
            </CardContent>
        </Card>

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

        <section class="grid gap-6 lg:grid-cols-3">
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

            <Card class="lg:col-span-3">
                <CardHeader>
                    <CardTitle>Interne tijdlijn</CardTitle>
                    <CardDescription
                        >Opmerkingen, besluiten en systeemactiviteiten. Alleen
                        zichtbaar voor het interne team.</CardDescription
                    >
                </CardHeader>
                <CardContent class="space-y-6">
                    <form
                        class="bg-muted/30 space-y-4 rounded-lg border p-4"
                        @submit.prevent="submitTimelineEntry"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div
                                class="flex gap-2"
                                role="group"
                                aria-label="Type tijdlijnbericht"
                            >
                                <Button
                                    type="button"
                                    size="sm"
                                    :variant="
                                        timelineForm.type === 'comment'
                                            ? 'default'
                                            : 'outline'
                                    "
                                    @click="timelineForm.type = 'comment'"
                                >
                                    Opmerking
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    :variant="
                                        timelineForm.type === 'decision'
                                            ? 'default'
                                            : 'outline'
                                    "
                                    @click="timelineForm.type = 'decision'"
                                >
                                    Besluit
                                </Button>
                            </div>
                            <p class="text-muted-foreground text-xs">
                                Intern — niet zichtbaar voor klanten
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="timeline_body">
                                {{
                                    timelineForm.type === 'decision'
                                        ? 'Besluit'
                                        : 'Opmerking'
                                }}
                            </Label>
                            <textarea
                                id="timeline_body"
                                v-model="timelineForm.body"
                                rows="4"
                                maxlength="5000"
                                :placeholder="
                                    timelineForm.type === 'decision'
                                        ? 'Leg vast wat is besloten en waarom.'
                                        : 'Deel voortgang, observaties of context met het team.'
                                "
                                class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            />
                            <InputError :message="timelineForm.errors.body" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Teamleden vermelden</Label>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-for="teamMember in teamMembers"
                                    :key="teamMember.id"
                                    type="button"
                                    size="sm"
                                    :variant="
                                        timelineForm.mention_ids.includes(
                                            teamMember.id,
                                        )
                                            ? 'secondary'
                                            : 'outline'
                                    "
                                    @click="toggleMention(teamMember.id)"
                                >
                                    @{{ teamMember.name }}
                                </Button>
                            </div>
                            <InputError
                                :message="timelineForm.errors.mention_ids"
                            />
                        </div>
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="grid gap-2">
                                <Label for="timeline_attachment">Bijlage</Label>
                                <Input
                                    id="timeline_attachment"
                                    type="file"
                                    accept=".pdf,.txt,.log,.csv,.json,.zip,.png,.jpg,.jpeg,.webp"
                                    @change="selectAttachment"
                                />
                                <InputError
                                    :message="timelineForm.errors.attachment"
                                />
                            </div>
                            <Button
                                :disabled="timelineForm.processing"
                                type="submit"
                            >
                                <Send /> Toevoegen
                            </Button>
                        </div>
                    </form>
                    <ol
                        v-if="issue.activities.length"
                        class="relative space-y-5 border-l pl-5"
                    >
                        <li
                            v-for="activity in issue.activities"
                            :key="activity.id"
                            class="relative"
                        >
                            <span
                                class="bg-primary absolute top-1.5 -left-[1.45rem] size-2.5 rounded-full"
                            />
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="activity.action === 'decision'"
                                    class="rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800"
                                >
                                    Besluit
                                </span>
                                <span
                                    v-else-if="activity.action === 'comment'"
                                    class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800"
                                >
                                    Interne opmerking
                                </span>
                            </div>
                            <p
                                class="mt-2 text-sm whitespace-pre-line"
                                :class="
                                    activity.action !== 'comment' &&
                                    activity.action !== 'decision'
                                        ? 'font-medium'
                                        : ''
                                "
                            >
                                {{ activity.description }}
                            </p>
                            <div
                                v-if="activity.mentions.length"
                                class="mt-2 flex flex-wrap gap-1.5"
                            >
                                <span
                                    v-for="mention in activity.mentions"
                                    :key="mention.id"
                                    class="bg-secondary text-secondary-foreground rounded-full px-2 py-0.5 text-xs font-medium"
                                >
                                    @{{ mention.name }}
                                </span>
                            </div>
                            <a
                                v-if="activity.attachment"
                                :href="activity.attachment.download_url"
                                class="text-primary mt-2 inline-flex items-center gap-1.5 text-sm font-medium hover:underline"
                            >
                                <Paperclip class="size-4" />
                                {{ activity.attachment.name }}
                            </a>
                            <p class="text-muted-foreground mt-1 text-xs">
                                {{ activity.user ?? 'Systeem' }} ·
                                {{ formatDate(activity.created_at) }}
                            </p>
                        </li>
                    </ol>
                    <p v-else class="text-muted-foreground text-sm">
                        Nog geen activiteiten.
                    </p>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
