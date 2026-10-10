<script setup lang="ts">
import WorkspaceHeader from '@/components/WorkspaceHeader.vue';
import WorkspacePage from '@/components/WorkspacePage.vue';
import { nowForInput } from '@/lib/dates';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { index as projectsIndex } from '@/routes/projects';
import { report, store } from '@/routes/issues';

type Project = {
    id: number;
    name: string;
    customer_name: string | null;
};

type ChecklistTemplate = {
    id: number;
    name: string;
    is_required: boolean;
    marks_issue_resolved: boolean;
};

const props = defineProps<{
    projects: Project[];
    checklistTemplates: ChecklistTemplate[];
}>();

const form = useForm({
    project_id: '',
    title: '',
    description: '',
    priority: 'p2',
    reported_at: nowForInput(),
    is_historical: false,
    first_responded_at: '',
    resolved_at: '',
    status: 'open',
    resolution_summary: '',
    cause: '',
    internal_note: '',
    checklist_completed: [] as number[],
});

const formElement = ref<HTMLFormElement>();
const submit = () => {
    if (form.processing) return;
    form.post(store.url(), {
        onFinish: async () => {
            if (!form.hasErrors) return;
            await nextTick();
            formElement.value
                ?.querySelector<HTMLElement>('[aria-invalid="true"]')
                ?.focus();
        },
    });
};

const isTemplateCompleted = (templateId: number) =>
    form.checklist_completed.includes(templateId);

const toggleTemplate = (templateId: number, checked: boolean) => {
    form.checklist_completed = checked
        ? [...form.checklist_completed, templateId]
        : form.checklist_completed.filter((id) => id !== templateId);
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Storing melden', href: report() },
        ],
    },
});
</script>

<template>
    <Head title="Storing melden" />

    <WorkspacePage compact>
        <WorkspaceHeader
            title="Storing melden"
            description="Registreer de storing voor het juiste project. De responders zijn aan het project gekoppeld."
        /><Card>
            <CardContent>
                <div
                    v-if="projects.length === 0"
                    class="rounded-lg border border-dashed p-5 text-sm"
                >
                    <p class="font-medium">Er zijn geen actieve projecten.</p>
                    <p class="text-muted-foreground mt-1">
                        Activeer een project of voeg eerst een nieuw project toe
                        voordat je een storing meldt.
                    </p>
                    <Button class="mt-4" variant="outline" as-child>
                        <Link :href="projectsIndex()">Naar projecten</Link>
                    </Button>
                </div>

                <form
                    v-else
                    ref="formElement"
                    :aria-busy="form.processing"
                    class="space-y-5"
                    @submit.prevent="submit"
                >
                    <div
                        class="border-border bg-accent/50 rounded-xl border p-4"
                    >
                        <h2 class="text-foreground text-sm font-semibold">
                            Snel registreren
                        </h2>
                        <p class="text-muted-foreground mt-1 text-sm">
                            Leg nu alleen project, titel, prioriteit en
                            starttijd vast. Vul details later aan.
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="project_id">Project (verplicht)</Label>
                        <select
                            id="project_id"
                            v-model="form.project_id"
                            required
                            autofocus
                            :aria-invalid="Boolean(form.errors.project_id)"
                            aria-describedby="project_id-error"
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
                                    — {{ project.customer_name }}
                                </template>
                            </option>
                        </select>
                        <InputError
                            id="project_id-error"
                            :message="form.errors.project_id"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="title">Titel (verplicht)</Label>
                        <Input
                            id="title"
                            maxlength="255"
                            v-model="form.title"
                            required
                            :aria-invalid="Boolean(form.errors.title)"
                            aria-describedby="title-error"
                        />
                        <InputError
                            id="title-error"
                            :message="form.errors.title"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description"
                            >Omschrijving
                            <span class="text-muted-foreground font-normal"
                                >(optioneel)</span
                            ></Label
                        >
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            :aria-invalid="Boolean(form.errors.description)"
                            aria-describedby="description-error"
                            class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-base shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2 md:text-sm"
                        />
                        <InputError
                            id="description-error"
                            :message="form.errors.description"
                        />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="priority">Prioriteit (verplicht)</Label>
                            <select
                                id="priority"
                                v-model="form.priority"
                                :aria-invalid="Boolean(form.errors.priority)"
                                aria-describedby="priority-error"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 text-base shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2 md:text-sm"
                            >
                                <option value="p1">P1 — kritiek</option>
                                <option value="p2">P2 — hoog</option>
                                <option value="p3">P3 — normaal</option>
                                <option value="p4">P4 — laag</option>
                            </select>
                            <InputError
                                id="priority-error"
                                :message="form.errors.priority"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="reported_at">
                                {{
                                    form.is_historical
                                        ? 'Gestart op'
                                        : 'Datum en tijd'
                                }}
                                (verplicht)
                            </Label>
                            <Input
                                id="reported_at"
                                v-model="form.reported_at"
                                :aria-invalid="Boolean(form.errors.reported_at)"
                                aria-describedby="reported_at-error"
                                type="datetime-local"
                                required
                            />
                            <InputError
                                id="reported_at-error"
                                :message="form.errors.reported_at"
                            />
                        </div>
                    </div>

                    <div class="bg-muted/30 rounded-xl border p-4 sm:p-5">
                        <label class="flex cursor-pointer items-start gap-3">
                            <Checkbox
                                id="is_historical"
                                :model-value="form.is_historical"
                                @update:model-value="
                                    form.is_historical = $event === true
                                "
                            />
                            <span class="grid gap-1">
                                <span
                                    class="text-foreground text-sm font-medium"
                                >
                                    Deze storing is achteraf geregistreerd
                                </span>
                                <span class="text-muted-foreground text-sm">
                                    Leg de werkelijke incidentmomenten vast. Het
                                    registratiemoment blijft apart bewaard.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div
                        v-if="form.is_historical"
                        class="space-y-5 rounded-xl border p-4 sm:p-5"
                    >
                        <div>
                            <h2 class="text-base font-semibold">
                                Historische afhandeling
                            </h2>
                            <p class="text-muted-foreground mt-1 text-sm">
                                Vul alleen de momenten en gegevens in die
                                tijdens de storing bekend zijn.
                            </p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="first_responded_at"
                                    >First response op</Label
                                >
                                <Input
                                    id="first_responded_at"
                                    v-model="form.first_responded_at"
                                    :aria-invalid="
                                        Boolean(form.errors.first_responded_at)
                                    "
                                    aria-describedby="first_responded_at-error"
                                    type="datetime-local"
                                />
                                <InputError
                                    id="first_responded_at-error"
                                    :message="form.errors.first_responded_at"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="resolved_at"
                                    >Technisch opgelost op</Label
                                >
                                <Input
                                    id="resolved_at"
                                    v-model="form.resolved_at"
                                    :aria-invalid="
                                        Boolean(form.errors.resolved_at)
                                    "
                                    aria-describedby="resolved_at-error"
                                    type="datetime-local"
                                />
                                <InputError
                                    id="resolved_at-error"
                                    :message="form.errors.resolved_at"
                                />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Eindstatus</Label>
                            <select
                                id="status"
                                v-model="form.status"
                                :aria-invalid="Boolean(form.errors.status)"
                                aria-describedby="status-error"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 text-base shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2 md:text-sm"
                            >
                                <option value="open">Open</option>
                                <option value="handling">In afhandeling</option>
                                <option value="completed">Afgerond</option>
                            </select>
                            <InputError
                                id="status-error"
                                :message="form.errors.status"
                            />
                        </div>

                        <div class="space-y-3 rounded-lg border p-4">
                            <div>
                                <h3 class="text-sm font-medium">
                                    Checklist bij afhandeling
                                </h3>
                                <p class="text-muted-foreground mt-1 text-sm">
                                    Voor Afgerond moeten alle verplichte items
                                    zijn vastgelegd.
                                </p>
                            </div>
                            <div
                                v-for="template in checklistTemplates"
                                :key="template.id"
                                class="flex items-start gap-3"
                            >
                                <Checkbox
                                    :id="`checklist-${template.id}`"
                                    :model-value="
                                        isTemplateCompleted(template.id)
                                    "
                                    :aria-invalid="
                                        Boolean(form.errors.checklist_completed)
                                    "
                                    aria-describedby="checklist_completed-error"
                                    @update:model-value="
                                        toggleTemplate(
                                            template.id,
                                            $event === true,
                                        )
                                    "
                                />
                                <label
                                    :for="`checklist-${template.id}`"
                                    class="cursor-pointer text-sm"
                                >
                                    {{ template.name }}
                                    <span
                                        v-if="template.is_required"
                                        class="text-muted-foreground"
                                        >(verplicht)</span
                                    >
                                    <span
                                        v-if="template.marks_issue_resolved"
                                        class="text-muted-foreground"
                                        >(oplossing)</span
                                    >
                                </label>
                            </div>
                            <InputError
                                id="checklist_completed-error"
                                :message="form.errors.checklist_completed"
                            />
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="cause">Oorzaak</Label>
                                <select
                                    id="cause"
                                    v-model="form.cause"
                                    :aria-invalid="Boolean(form.errors.cause)"
                                    aria-describedby="cause-error"
                                    class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 text-base shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2 md:text-sm"
                                >
                                    <option value="">
                                        Nog niet vastgesteld
                                    </option>
                                    <option value="internal_knowledge_gap">
                                        Kennis ontbreekt intern
                                    </option>
                                    <option value="customer_knowledge_gap">
                                        Kennis ontbreekt bij klant
                                    </option>
                                    <option value="user_error">
                                        Gebruikersfout
                                    </option>
                                    <option value="code_defect">Codebug</option>
                                    <option value="configuration_error">
                                        Configuratiefout
                                    </option>
                                    <option value="infrastructure">
                                        Infrastructuur
                                    </option>
                                    <option value="external_dependency">
                                        Externe afhankelijkheid
                                    </option>
                                    <option value="other">Anders</option>
                                </select>
                                <InputError
                                    id="cause-error"
                                    :message="form.errors.cause"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label for="resolution_summary"
                                    >Oplossing / afhandeling</Label
                                >
                                <Input
                                    id="resolution_summary"
                                    v-model="form.resolution_summary"
                                    :aria-invalid="
                                        Boolean(form.errors.resolution_summary)
                                    "
                                    aria-describedby="resolution_summary-error"
                                />
                                <InputError
                                    id="resolution_summary-error"
                                    :message="form.errors.resolution_summary"
                                />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="internal_note">Interne notitie</Label>
                            <textarea
                                id="internal_note"
                                v-model="form.internal_note"
                                :aria-invalid="
                                    Boolean(form.errors.internal_note)
                                "
                                aria-describedby="internal_note-error"
                                rows="3"
                                class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-base shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2 md:text-sm"
                            />
                            <InputError
                                id="internal_note-error"
                                :message="form.errors.internal_note"
                            />
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-1">
                        <Button :disabled="form.processing" type="submit">
                            Storing opslaan
                        </Button>
                        <Button variant="outline" as-child>
                            <Link :href="dashboard()">Annuleren</Link>
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </WorkspacePage>
</template>
