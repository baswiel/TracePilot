<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Paperclip, Send } from '@lucide/vue';
import { store as storeTimelineEntry } from '@/routes/issues/timeline';
import { formatDate } from '@/lib/dates';
import type { Issue, TeamMember } from '@/types/issues';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
const props = defineProps<{
    issueId: number;
    activities: Issue['activities'];
    teamMembers: TeamMember[];
}>();
const timelineForm = useForm({
    type: 'comment' as 'comment' | 'decision',
    body: '',
    mention_ids: [] as number[],
    attachment: null as File | null,
});
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
    timelineForm.post(storeTimelineEntry(props.issueId).url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            timelineForm.reset();
            timelineForm.type = 'comment';
        },
    });
};
</script>
<template>
    <Card class="lg:col-span-3">
        <CardHeader>
            <CardTitle>Interne tijdlijn</CardTitle>
            <CardDescription
                >Opmerkingen, besluiten en systeemactiviteiten. Alleen zichtbaar
                voor het interne team.</CardDescription
            >
        </CardHeader>
        <CardContent class="space-y-6">
            <form
                class="bg-muted/30 space-y-4 rounded-lg border p-4"
                @submit.prevent="submitTimelineEntry"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
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
                        :aria-invalid="Boolean(timelineForm.errors.body)"
                        aria-describedby="timeline_body-error"
                    />
                    <InputError
                        id="timeline_body-error"
                        :message="timelineForm.errors.body"
                    />
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
                                timelineForm.mention_ids.includes(teamMember.id)
                                    ? 'secondary'
                                    : 'outline'
                            "
                            @click="toggleMention(teamMember.id)"
                        >
                            @{{ teamMember.name }}
                        </Button>
                    </div>
                    <InputError :message="timelineForm.errors.mention_ids" />
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
                        <InputError :message="timelineForm.errors.attachment" />
                    </div>
                    <Button :disabled="timelineForm.processing" type="submit">
                        <Send /> Toevoegen
                    </Button>
                </div>
            </form>
            <ol
                v-if="activities.length"
                class="relative space-y-5 border-l pl-5"
            >
                <li
                    v-for="activity in activities"
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
</template>
