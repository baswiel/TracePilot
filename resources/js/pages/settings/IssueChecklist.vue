<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    CheckCircle2,
    Pencil,
    Plus,
    Power,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import InputError from '@/components/InputError.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
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
import { destroy, index, move, store, update } from '@/routes/issue-checklist';

type Template = {
    id: number;
    name: string;
    is_required: boolean;
    marks_issue_resolved: boolean;
    is_active: boolean;
    sort_order: number;
};

const props = defineProps<{ templates: Template[] }>();
const editingId = ref<number | null>(null);
const editingTemplate = computed(
    () =>
        props.templates.find((template) => template.id === editingId.value) ??
        null,
);

const form = useForm({
    name: '',
    is_required: true,
    marks_issue_resolved: false,
    is_active: true,
    sort_order: 1,
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Issue-checklist', href: index() },
        ],
    },
});

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.sort_order = props.templates.length + 1;
};

const editTemplate = (template: Template) => {
    editingId.value = template.id;
    form.name = template.name;
    form.is_required = template.is_required;
    form.marks_issue_resolved = template.marks_issue_resolved;
    form.is_active = template.is_active;
    form.sort_order = template.sort_order;
    form.clearErrors();
};

const submit = () => {
    if (editingTemplate.value) {
        form.patch(update(editingTemplate.value.id).url, {
            onSuccess: resetForm,
        });

        return;
    }

    form.post(store.url(), { onSuccess: resetForm });
};

const moveTemplate = (template: Template, direction: 'up' | 'down') => {
    router.patch(
        move(template.id).url,
        { direction },
        { preserveScroll: true },
    );
};

const applyActive = (template: Template) => {
    router.patch(
        update(template.id).url,
        {
            name: template.name,
            is_required: template.is_required,
            marks_issue_resolved: template.marks_issue_resolved,
            is_active: !template.is_active,
            sort_order: template.sort_order,
        },
        { preserveScroll: true },
    );
};

const templateToDelete = ref<Template | null>(null);
const templateToToggle = ref<Template | null>(null);
const deleting = ref(false);
const deleteTemplate = () => {
    if (!templateToDelete.value || deleting.value) return;
    router.delete(destroy(templateToDelete.value.id).url, {
        preserveScroll: true,
        onStart: () => {
            deleting.value = true;
        },
        onFinish: () => {
            deleting.value = false;
        },
        onSuccess: () => {
            templateToDelete.value = null;
        },
    });
};
const confirmToggle = () => {
    if (!templateToToggle.value) return;
    applyActive(templateToToggle.value);
    templateToToggle.value = null;
};
</script>

<template>
    <Head title="Issue-checklist" />

    <div class="flex flex-col gap-6">
        <Heading
            title="Issue-checklist"
            description="Bepaal welke stappen standaard bij iedere nieuwe storing horen."
        />

        <Card>
            <CardHeader>
                <CardTitle>{{
                    editingTemplate
                        ? 'Checklist-item bewerken'
                        : 'Checklist-item toevoegen'
                }}</CardTitle>
                <CardDescription>
                    De ingestelde volgorde wordt gebruikt bij nieuwe storingen.
                    Bestaande storingen behouden hun eigen snapshot.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-5" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="name">Naam</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            placeholder="Bijvoorbeeld: Storing opgelost"
                            :aria-invalid="Boolean(form.errors.name)"
                            aria-describedby="checklist-name-error"
                        />
                        <InputError
                            id="checklist-name-error"
                            :message="form.errors.name"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="sort_order">Volgorde</Label>
                        <Input
                            id="sort_order"
                            v-model.number="form.sort_order"
                            min="1"
                            type="number"
                            required
                        />
                        <InputError
                            id="checklist-sort_order-error"
                            :message="form.errors.sort_order"
                        />
                    </div>

                    <div class="space-y-3 rounded-lg border p-4">
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                v-model="form.is_required"
                                class="accent-primary size-4"
                                type="checkbox"
                            />
                            <span
                                ><strong>Verplicht</strong> — dit item is nodig
                                om een storing af te ronden.</span
                            >
                        </label>
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                v-model="form.marks_issue_resolved"
                                class="accent-primary size-4"
                                type="checkbox"
                            />
                            <span
                                ><strong>Markeert storing als opgelost</strong>
                                — maximaal één actief item.</span
                            >
                        </label>
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                v-model="form.is_active"
                                class="accent-primary size-4"
                                type="checkbox"
                            />
                            <span
                                ><strong>Actief</strong> — alleen actieve items
                                worden naar nieuwe storingen gekopieerd.</span
                            >
                        </label>
                    </div>
                    <InputError
                        :message="
                            form.errors.is_required ||
                            form.errors.marks_issue_resolved ||
                            form.errors.is_active
                        "
                    />

                    <div class="flex flex-wrap gap-3">
                        <Button :disabled="form.processing" type="submit">
                            <Plus v-if="!editingTemplate" />
                            {{
                                editingTemplate
                                    ? 'Wijzigingen opslaan'
                                    : 'Item toevoegen'
                            }}
                        </Button>
                        <Button
                            v-if="editingTemplate"
                            type="button"
                            variant="outline"
                            @click="resetForm"
                            >Annuleren</Button
                        >
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Checklisttemplates</CardTitle>
                <CardDescription
                    >Gebruik Omhoog en Omlaag om de volgorde veilig te
                    wijzigen.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <div v-if="templates.length" class="divide-y rounded-lg border">
                    <article
                        v-for="(template, position) in templates"
                        :key="template.id"
                        class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center"
                    >
                        <div class="flex shrink-0 gap-1">
                            <Button
                                :disabled="position === 0"
                                size="icon-sm"
                                variant="outline"
                                @click="moveTemplate(template, 'up')"
                            >
                                <ArrowUp /><span class="sr-only">Omhoog</span>
                            </Button>
                            <Button
                                :disabled="position === templates.length - 1"
                                size="icon-sm"
                                variant="outline"
                                @click="moveTemplate(template, 'down')"
                            >
                                <ArrowDown /><span class="sr-only">Omlaag</span>
                            </Button>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">
                                {{ template.sort_order }}. {{ template.name }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <Badge
                                    :variant="
                                        template.is_required
                                            ? 'default'
                                            : 'secondary'
                                    "
                                    >{{
                                        template.is_required
                                            ? 'Verplicht'
                                            : 'Optioneel'
                                    }}</Badge
                                >
                                <Badge
                                    v-if="template.marks_issue_resolved"
                                    variant="outline"
                                    ><CheckCircle2 /> Markeert storing als
                                    opgelost</Badge
                                >
                                <Badge
                                    :variant="
                                        template.is_active
                                            ? 'outline'
                                            : 'secondary'
                                    "
                                    >{{
                                        template.is_active
                                            ? 'Actief'
                                            : 'Inactief'
                                    }}</Badge
                                >
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="editTemplate(template)"
                                ><Pencil /> Bewerken</Button
                            >
                            <Button
                                size="sm"
                                variant="outline"
                                @click="templateToToggle = template"
                                ><Power />
                                {{
                                    template.is_active
                                        ? 'Deactiveren'
                                        : 'Activeren'
                                }}</Button
                            >
                            <Button
                                v-if="!template.is_active"
                                size="sm"
                                variant="destructive"
                                @click="templateToDelete = template"
                                ><Trash2 /> Verwijderen</Button
                            >
                        </div>
                    </article>
                </div>
                <div
                    v-else
                    class="rounded-lg border border-dashed px-5 py-10 text-center"
                >
                    <p class="font-medium">Nog geen checklisttemplates</p>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Voeg minimaal één actief verplicht item toe.
                    </p>
                </div>
            </CardContent>
        </Card>
        <ConfirmDeleteDialog
            :open="Boolean(templateToDelete)"
            @update:open="!$event && (templateToDelete = null)"
            title="Checklist-item verwijderen"
            :description="`Wil je '${templateToDelete?.name ?? ''}' verwijderen? Bestaande storingen houden hun checklist.`"
            :processing="deleting"
            @confirm="deleteTemplate"
        />
        <ConfirmDeleteDialog
            :open="Boolean(templateToToggle)"
            @update:open="!$event && (templateToToggle = null)"
            title="Checklist-item wijzigen"
            :description="`Wil je '${templateToToggle?.name ?? ''}' ${templateToToggle?.is_active ? 'deactiveren' : 'activeren'}? Dit geldt voor nieuwe storingen.`"
            :confirm-label="
                templateToToggle?.is_active ? 'Deactiveren' : 'Activeren'
            "
            @confirm="confirmToggle"
        />
    </div>
</template>
