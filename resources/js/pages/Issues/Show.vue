<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Check, CircleSlash, Pencil, RotateCcw } from '@lucide/vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { update as updateChecklistItem } from '@/routes/issues/checklist';
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
    elapsed_duration: string;
    resolved_at: string | null;
    completed_at: string | null;
    project: { id: number; name: string; customer_name: string | null };
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
    activities: Array<{
        id: number;
        action: string;
        description: string;
        created_at: string;
        user: string | null;
    }>;
};

type TeamMember = { id: number; name: string; email: string | null };

const props = defineProps<{ issue: Issue; teamMembers: TeamMember[] }>();
const isEditing = ref(false);
const updatingItemIds = ref<number[]>([]);
const detailsForm = useForm({
    title: props.issue.title,
    description: props.issue.description ?? '',
    priority: props.issue.priority,
    team_member_id: props.issue.team_member_id ?? '',
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

    updatingItemIds.value = [...updatingItemIds.value, item.id];
    router.patch(
        updateChecklistItem([props.issue.id, item.id]).url,
        { is_completed: !item.is_completed, is_not_applicable: false },
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
                        <div v-if="issue.resolved_at">
                            <dt class="text-muted-foreground text-sm">
                                Technisch opgelost
                            </dt>
                            <dd class="mt-1 text-sm">
                                {{ issue.resolved_at }}
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

            <Card>
                <CardHeader>
                    <CardTitle>Activiteiten</CardTitle>
                    <CardDescription
                        >Meest recente activiteit eerst.</CardDescription
                    >
                </CardHeader>
                <CardContent>
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
                            <p class="text-sm font-medium">
                                {{ activity.description }}
                            </p>
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
