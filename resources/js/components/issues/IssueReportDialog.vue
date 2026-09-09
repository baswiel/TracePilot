<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import { store } from '@/routes/issues';

type Project = { id: number; name: string; customer_name: string | null };
type TeamMember = { id: number; name: string; email: string };

const props = defineProps<{ projects: Project[]; teamMembers: TeamMember[] }>();
const open = defineModel<boolean>('open', { default: false });
const nowForInput = () => new Date().toISOString().slice(0, 16);
const form = useForm({
    project_id: '',
    title: '',
    description: '',
    priority: 'p2',
    reported_at: nowForInput(),
    team_member_id: '',
});
const hasProjects = computed(() => props.projects.length > 0);

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.reported_at = nowForInput();
    }
});

const submit = () => form.post(store.url());
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl"
        >
            <DialogHeader>
                <DialogTitle>Storing melden</DialogTitle>
                <DialogDescription
                    >Registreer de storing en wijs desgewenst direct een
                    verantwoordelijke toe.</DialogDescription
                >
            </DialogHeader>

            <div
                v-if="!hasProjects"
                class="rounded-lg border border-dashed p-5 text-sm"
            >
                <p class="font-medium">Er zijn geen actieve projecten.</p>
                <p class="text-muted-foreground mt-1">
                    Activeer eerst een project voordat je een storing meldt.
                </p>
            </div>

            <form v-else class="space-y-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="report-project_id">Project</Label>
                    <select
                        id="report-project_id"
                        v-model="form.project_id"
                        required
                        autofocus
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    >
                        <option disabled value="">Kies een project</option>
                        <option
                            v-for="project in projects"
                            :key="project.id"
                            :value="project.id"
                        >
                            {{ project.name
                            }}<template v-if="project.customer_name">
                                — {{ project.customer_name }}</template
                            >
                        </option>
                    </select>
                    <InputError :message="form.errors.project_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="report-title">Titel</Label>
                    <Input id="report-title" v-model="form.title" required />
                    <InputError :message="form.errors.title" />
                </div>
                <div class="grid gap-2">
                    <Label for="report-description">Omschrijving</Label>
                    <textarea
                        id="report-description"
                        v-model="form.description"
                        rows="4"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    />
                    <InputError :message="form.errors.description" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="report-priority">Prioriteit</Label>
                        <select
                            id="report-priority"
                            v-model="form.priority"
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                        >
                            <option value="p1">P1 — kritiek</option>
                            <option value="p2">P2 — hoog</option>
                            <option value="p3">P3 — normaal</option>
                            <option value="p4">P4 — laag</option>
                        </select>
                        <InputError :message="form.errors.priority" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="report-reported_at">Datum en tijd</Label>
                        <Input
                            id="report-reported_at"
                            v-model="form.reported_at"
                            type="datetime-local"
                            required
                        />
                        <InputError :message="form.errors.reported_at" />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="report-team_member_id">Verantwoordelijke</Label>
                    <select
                        id="report-team_member_id"
                        v-model="form.team_member_id"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    >
                        <option value="">Nog niet toegewezen</option>
                        <option
                            v-for="teamMember in teamMembers"
                            :key="teamMember.id"
                            :value="teamMember.id"
                        >
                            {{ teamMember.name
                            }}<template v-if="teamMember.email">
                                ({{ teamMember.email }})</template
                            >
                        </option>
                    </select>
                    <InputError :message="form.errors.team_member_id" />
                </div>
                <DialogFooter class="pt-1">
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Annuleren</Button
                    >
                    <Button :disabled="form.processing" type="submit"
                        >Storing opslaan</Button
                    >
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
