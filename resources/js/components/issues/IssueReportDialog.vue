<script setup lang="ts">
import { nowForInput } from '@/lib/dates';
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
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

const props = defineProps<{ projects: Project[] }>();
const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ closed: [] }>();

const restoreFocus = (event: Event) => {
    event.preventDefault();
    emit('closed');
};

const form = useForm({
    project_id: '',
    title: '',
    description: '',
    priority: 'p2',
    reported_at: nowForInput(),
});
const formElement = ref<HTMLFormElement>();
const submissionError = ref('');
const hasProjects = computed(() => props.projects.length > 0);

watch(open, (isOpen) => {
    if (isOpen && !form.isDirty) {
        form.reported_at = nowForInput();
    }
});

const submit = () => {
    if (form.processing) return;

    submissionError.value = '';
    form.post(store.url(), {
        onSuccess: () => {
            form.resetAndClearErrors();
            form.reported_at = nowForInput();
            open.value = false;
        },
        onFinish: async () => {
            if (!form.hasErrors) return;
            await nextTick();
            formElement.value
                ?.querySelector<HTMLElement>('[aria-invalid="true"]')
                ?.focus();
        },
        onNetworkError: () => {
            submissionError.value =
                'Opslaan kon niet worden bevestigd. Controleer je verbinding en de storingenlijst voordat je opnieuw probeert. Je invoer blijft bewaard.';
            return false;
        },
        onHttpException: (response) => {
            if (response.status === 403) {
                submissionError.value =
                    'Je hebt geen toestemming om deze storing te melden. Vraag je beheerder om toegang.';
            } else if (response.status === 429) {
                submissionError.value =
                    'Je hebt te vaak geprobeerd op te slaan. Wacht even en probeer opnieuw.';
            } else if (response.status >= 500) {
                submissionError.value =
                    'Opslaan kon niet worden bevestigd. Controleer de storingenlijst voordat je opnieuw probeert. Je invoer blijft bewaard.';
            } else {
                return;
            }
            return false;
        },
    });
};

const updateOpen = (value: boolean) => {
    if (!form.processing) open.value = value;
};
</script>

<template>
    <Dialog :open="open" @update:open="updateOpen">
        <DialogContent
            class="max-h-[calc(100dvh-2rem)] overflow-y-auto sm:max-w-2xl"
            :show-close-button="!form.processing"
            @close-auto-focus="restoreFocus"
        >
            <DialogHeader>
                <DialogTitle>Storing melden</DialogTitle>
                <DialogDescription
                    >Registreer de storing voor het juiste project. De
                    responders zijn aan het project
                    gekoppeld.</DialogDescription
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

            <form
                v-else
                ref="formElement"
                :aria-busy="form.processing"
                @submit.prevent="submit"
            >
                <p
                    v-if="submissionError"
                    role="alert"
                    class="text-destructive mb-5 text-sm"
                >
                    {{ submissionError }}
                </p>
                <fieldset :disabled="form.processing" class="min-w-0 space-y-5">
                    <div class="grid gap-2">
                        <Label for="report-project_id"
                            >Project (verplicht)</Label
                        >
                        <select
                            id="report-project_id"
                            v-model="form.project_id"
                            :aria-invalid="Boolean(form.errors.project_id)"
                            :aria-describedby="
                                form.errors.project_id
                                    ? 'report-project_id-error'
                                    : undefined
                            "
                            required
                            autofocus
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 text-base shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2 md:text-sm"
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
                        <InputError
                            id="report-project_id-error"
                            :message="form.errors.project_id"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="report-title">Titel (verplicht)</Label>
                        <Input
                            id="report-title"
                            maxlength="255"
                            v-model="form.title"
                            :aria-invalid="Boolean(form.errors.title)"
                            :aria-describedby="
                                form.errors.title
                                    ? 'report-title-error'
                                    : undefined
                            "
                            required
                        />
                        <InputError
                            id="report-title-error"
                            :message="form.errors.title"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="report-description">Omschrijving</Label>
                        <textarea
                            id="report-description"
                            v-model="form.description"
                            :aria-invalid="Boolean(form.errors.description)"
                            :aria-describedby="
                                form.errors.description
                                    ? 'report-description-error'
                                    : undefined
                            "
                            rows="4"
                            class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-base shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2 md:text-sm"
                        />
                        <InputError
                            id="report-description-error"
                            :message="form.errors.description"
                        />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="report-priority"
                                >Prioriteit (verplicht)</Label
                            >
                            <select
                                id="report-priority"
                                v-model="form.priority"
                                :aria-invalid="Boolean(form.errors.priority)"
                                :aria-describedby="
                                    form.errors.priority
                                        ? 'report-priority-error'
                                        : undefined
                                "
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 text-base shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2 md:text-sm"
                            >
                                <option value="p1">P1 — kritiek</option>
                                <option value="p2">P2 — hoog</option>
                                <option value="p3">P3 — normaal</option>
                                <option value="p4">P4 — laag</option>
                            </select>
                            <InputError
                                id="report-priority-error"
                                :message="form.errors.priority"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="report-reported_at"
                                >Datum en tijd (verplicht)</Label
                            >
                            <Input
                                id="report-reported_at"
                                v-model="form.reported_at"
                                :aria-invalid="Boolean(form.errors.reported_at)"
                                :aria-describedby="
                                    form.errors.reported_at
                                        ? 'report-reported_at-error'
                                        : undefined
                                "
                                type="datetime-local"
                                required
                            />
                            <InputError
                                id="report-reported_at-error"
                                :message="form.errors.reported_at"
                            />
                        </div>
                    </div>
                    <DialogFooter class="pt-1">
                        <Button
                            type="button"
                            variant="outline"
                            @click="updateOpen(false)"
                            >Annuleren</Button
                        >
                        <Button :disabled="form.processing" type="submit">{{
                            form.processing
                                ? 'Bezig met opslaan…'
                                : 'Storing opslaan'
                        }}</Button>
                    </DialogFooter>
                </fieldset>
            </form>
        </DialogContent>
    </Dialog>
</template>
