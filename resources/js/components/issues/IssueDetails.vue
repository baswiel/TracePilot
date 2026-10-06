<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { dateTimeForInput } from '@/lib/dates';
import { update } from '@/routes/issues';
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
const props = defineProps<{ issue: Issue; teamMembers: TeamMember[] }>();
const isEditing = defineModel<boolean>('editing', { default: false });
const currentDetails = () => ({
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
const detailsForm = useForm(currentDetails());
watch(isEditing, (editing) => {
    if (editing) {
        detailsForm.defaults(currentDetails());
        detailsForm.reset();
        detailsForm.clearErrors();
    }
});
const submitDetails = () => {
    detailsForm.put(update(props.issue.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
        },
    });
};
</script>
<template>
    <Card class="lg:col-span-2">
        <CardHeader class="flex-row items-start justify-between gap-4">
            <div>
                <CardTitle>Issuegegevens</CardTitle>
                <CardDescription>
                    Details en verantwoordelijke van deze storing.
                </CardDescription>
            </div>
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
                        :aria-invalid="Boolean(detailsForm.errors.title)"
                        aria-describedby="title-error"
                    />
                    <InputError
                        id="title-error"
                        :message="detailsForm.errors.title"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="description">Omschrijving</Label>
                    <textarea
                        id="description"
                        v-model="detailsForm.description"
                        rows="5"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                        :aria-invalid="Boolean(detailsForm.errors.description)"
                        aria-describedby="description-error"
                    />
                    <InputError
                        id="description-error"
                        :message="detailsForm.errors.description"
                    />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="priority">Prioriteit</Label>
                        <select
                            id="priority"
                            v-model="detailsForm.priority"
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            :aria-invalid="Boolean(detailsForm.errors.priority)"
                            aria-describedby="priority-error"
                        >
                            <option value="p1">P1 — kritiek</option>
                            <option value="p2">P2 — hoog</option>
                            <option value="p3">P3 — normaal</option>
                            <option value="p4">P4 — laag</option>
                        </select>
                        <InputError
                            id="priority-error"
                            :message="detailsForm.errors.priority"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="team_member_id">Verantwoordelijke</Label>
                        <select
                            id="team_member_id"
                            v-model="detailsForm.team_member_id"
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                            :aria-invalid="
                                Boolean(detailsForm.errors.team_member_id)
                            "
                            aria-describedby="team_member_id-error"
                        >
                            <option value="">Nog niet toegewezen</option>
                            <option
                                v-for="teamMember in teamMembers"
                                :key="teamMember.id"
                                :value="teamMember.id"
                            >
                                {{ teamMember.name }}
                            </option>
                        </select>
                        <InputError
                            id="team_member_id-error"
                            :message="detailsForm.errors.team_member_id"
                        />
                    </div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="reported_at">Gestart op</Label>
                        <Input
                            id="reported_at"
                            v-model="detailsForm.reported_at"
                            required
                            type="datetime-local"
                            :aria-invalid="
                                Boolean(detailsForm.errors.reported_at)
                            "
                            aria-describedby="reported_at-error"
                        />
                        <InputError
                            id="reported_at-error"
                            :message="detailsForm.errors.reported_at"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="first_responded_at"
                            >First response op</Label
                        >
                        <Input
                            id="first_responded_at"
                            v-model="detailsForm.first_responded_at"
                            type="datetime-local"
                            :aria-invalid="
                                Boolean(detailsForm.errors.first_responded_at)
                            "
                            aria-describedby="first_responded_at-error"
                        />
                        <InputError
                            :message="detailsForm.errors.first_responded_at"
                        />
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="resolved_at">Technisch opgelost op</Label>
                        <Input
                            id="resolved_at"
                            v-model="detailsForm.resolved_at"
                            type="datetime-local"
                            :aria-invalid="
                                Boolean(detailsForm.errors.resolved_at)
                            "
                            aria-describedby="resolved_at-error"
                        />
                        <InputError
                            id="resolved_at-error"
                            :message="detailsForm.errors.resolved_at"
                        />
                    </div>
                </div>
                <div class="space-y-3 rounded-lg border p-4">
                    <label class="flex items-center gap-3 text-sm">
                        <input
                            v-model="detailsForm.knowledge_base_recorded"
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
                    <Button :disabled="detailsForm.processing" type="submit">
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
                    <dt class="text-muted-foreground text-sm">Omschrijving</dt>
                    <dd class="mt-1 text-sm whitespace-pre-line">
                        {{
                            issue.description || 'Geen omschrijving opgegeven.'
                        }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground text-sm">
                        Verantwoordelijke
                    </dt>
                    <dd class="mt-1 text-sm">
                        {{ issue.assigned_to_name || 'Niet toegewezen' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground text-sm">Gemeld</dt>
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
                            issue.first_responded_at_label ||
                            'Nog niet vastgelegd'
                        }}
                    </dd>
                </div>
                <div v-if="issue.resolved_at">
                    <dt class="text-muted-foreground text-sm">
                        Technisch opgelost
                    </dt>
                    <dd class="mt-1 text-sm">
                        {{ issue.resolved_at_label }}
                    </dd>
                </div>
                <div v-if="issue.resolution_summary" class="sm:col-span-2">
                    <dt class="text-muted-foreground text-sm">Oplossing</dt>
                    <dd class="mt-1 text-sm whitespace-pre-line">
                        {{ issue.resolution_summary }}
                    </dd>
                </div>
                <div v-if="issue.cause">
                    <dt class="text-muted-foreground text-sm">Type storing</dt>
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
                                external_dependency: 'Externe afhankelijkheid',
                                other: 'Anders',
                            }[issue.cause]
                        }}
                    </dd>
                </div>
                <div v-if="issue.completed_at">
                    <dt class="text-muted-foreground text-sm">Afgerond</dt>
                    <dd class="mt-1 text-sm">
                        {{ issue.completed_at }}
                    </dd>
                </div>
                <div>
                    <dt class="text-muted-foreground text-sm">Kennisbank</dt>
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
</template>
