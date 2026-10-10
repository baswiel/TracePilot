<script setup lang="ts">
import type { IssuePriority } from '@/types/issues';
import { Head, router, useForm } from '@inertiajs/vue3';
import IssuePriorityBadge from '@/components/issues/IssuePriorityBadge.vue';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
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
import { destroy, index, store, update } from '@/routes/sla-levels';

type Target = {
    priority: IssuePriority;
    response_minutes: number;
    resolution_minutes: number;
};
type SlaLevel = { id: number; name: string; targets: Target[] };

const props = defineProps<{ slaLevels: SlaLevel[] }>();
const dialogOpen = ref(false);
const editingId = ref<number | null>(null);
const editingLevel = computed(
    () => props.slaLevels.find((level) => level.id === editingId.value) ?? null,
);
const emptyTargets = (): Target[] =>
    ['p1', 'p2', 'p3', 'p4'].map((priority) => ({
        priority: priority as Target['priority'],
        response_minutes: 60,
        resolution_minutes: 240,
    }));
const form = useForm({ name: '', targets: emptyTargets() });

const reset = () => {
    dialogOpen.value = false;
    editingId.value = null;
    form.reset();
    form.targets = emptyTargets();
    form.clearErrors();
};
const create = () => {
    reset();
    dialogOpen.value = true;
};
const edit = (level: SlaLevel) => {
    editingId.value = level.id;
    form.name = level.name;
    form.targets = level.targets.map((target) => ({ ...target }));
    form.clearErrors();
    dialogOpen.value = true;
};
const submit = () =>
    editingLevel.value
        ? form.patch(update(editingLevel.value.id).url, { onSuccess: reset })
        : form.post(store.url(), { onSuccess: reset });
const levelToDelete = ref<SlaLevel | null>(null);
const deleting = ref(false);
const remove = () => {
    if (!levelToDelete.value || deleting.value) return;
    router.delete(destroy(levelToDelete.value.id).url, {
        onStart: () => {
            deleting.value = true;
        },
        onFinish: () => {
            deleting.value = false;
        },
        onSuccess: () => {
            levelToDelete.value = null;
        },
    });
};
const duration = (minutes: number) =>
    minutes < 60
        ? `${minutes} min`
        : `${Math.floor(minutes / 60)} u${minutes % 60 ? ` ${minutes % 60} min` : ''}`;
const priorityLabel = (priority: Target['priority']) => priority.toUpperCase();
</script>

<template>
    <Head title="SLA-niveaus" />

    <div class="space-y-8">
        <div>
            <h2 class="text-lg font-medium">SLA-niveaus</h2>
            <p class="text-muted-foreground mt-1 text-sm">
                Definieer reactie- en oplostijden per prioriteit. Projecten
                kiezen vervolgens een niveau.
            </p>
        </div>
        <div class="flex justify-end">
            <Button @click="create"><Plus /> SLA-niveau toevoegen</Button>
        </div>

        <Dialog v-model:open="dialogOpen" @update:open="!$event && reset()">
            <DialogContent
                class="max-h-[calc(100vh-2rem)] w-[calc(100%-2rem)] min-w-0 overflow-y-auto sm:max-w-2xl"
            >
                <DialogHeader>
                    <DialogTitle>
                        {{
                            editingLevel
                                ? 'SLA-niveau bewerken'
                                : 'SLA-niveau toevoegen'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        Stel per prioriteit de reactie- en oplostijd in.
                    </DialogDescription>
                </DialogHeader>

                <form class="min-w-0 space-y-5" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="sla-level-name">Naam</Label>
                        <Input
                            id="sla-level-name"
                            v-model="form.name"
                            required
                            placeholder="Bijvoorbeeld: Gold"
                            :aria-invalid="Boolean(form.errors.name)"
                            aria-describedby="sla-name-error"
                        />
                        <InputError
                            id="sla-name-error"
                            :message="form.errors.name"
                        />
                    </div>
                    <p class="text-muted-foreground text-sm sm:hidden">
                        Veeg horizontaal om de reactie- en oplostijden te
                        bewerken.
                    </p>
                    <div
                        class="max-w-full min-w-0 overflow-x-auto rounded-lg border"
                        role="region"
                        aria-label="SLA-targets, horizontaal scrollbaar"
                        tabindex="0"
                    >
                        <table class="w-full min-w-115 text-sm">
                            <thead class="text-muted-foreground bg-muted/40">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">
                                        Prioriteit
                                    </th>
                                    <th class="px-3 py-2 text-left font-medium">
                                        Reactie (minuten)
                                    </th>
                                    <th class="px-3 py-2 text-left font-medium">
                                        Oplossen (minuten)
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="(target, position) in form.targets"
                                    :key="target.priority"
                                >
                                    <td class="px-3 py-2 font-medium">
                                        {{ priorityLabel(target.priority) }}
                                    </td>
                                    <td class="p-2">
                                        <Input
                                            :aria-label="`${priorityLabel(target.priority)} reactie in minuten`"
                                            v-model.number="
                                                target.response_minutes
                                            "
                                            min="1"
                                            type="number"
                                        />
                                        <InputError
                                            :message="
                                                form.errors[
                                                    `targets.${position}.response_minutes`
                                                ]
                                            "
                                        />
                                    </td>
                                    <td class="p-2">
                                        <Input
                                            :aria-label="`${priorityLabel(target.priority)} oplossing in minuten`"
                                            v-model.number="
                                                target.resolution_minutes
                                            "
                                            min="1"
                                            type="number"
                                        />
                                        <InputError
                                            :message="
                                                form.errors[
                                                    `targets.${position}.resolution_minutes`
                                                ]
                                            "
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="reset">
                            Annuleren
                        </Button>
                        <Button :disabled="form.processing" type="submit">
                            {{
                                editingLevel
                                    ? 'Wijzigingen opslaan'
                                    : 'SLA-niveau toevoegen'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <div class="grid gap-5 xl:grid-cols-2">
            <article
                v-for="level in slaLevels"
                :key="level.id"
                class="bg-card overflow-hidden rounded-xl border"
            >
                <header
                    class="flex flex-wrap items-center justify-between gap-3 border-b p-5"
                >
                    <h3 class="text-lg font-semibold break-words">
                        {{ level.name }}
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" @click="edit(level)"
                            ><Pencil /> Bewerken</Button
                        ><Button
                            variant="outline"
                            @click="levelToDelete = level"
                            ><Trash2 /><span class="sr-only"
                                >{{ level.name }} verwijderen</span
                            ></Button
                        >
                    </div>
                </header>
                <div class="p-5">
                    <p class="text-muted-foreground mb-3 text-xs">
                        Reactie- en oplostijden in werkuren
                    </p>
                    <table class="w-full text-left text-sm">
                        <thead class="text-muted-foreground text-xs">
                            <tr>
                                <th class="pb-3 font-medium">Prioriteit</th>
                                <th class="pb-3 font-medium">Reactie</th>
                                <th class="pb-3 font-medium">Oplossen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="target in level.targets"
                                :key="target.priority"
                            >
                                <td class="py-3">
                                    <IssuePriorityBadge
                                        :priority="target.priority"
                                    />
                                </td>
                                <td class="py-3 tabular-nums">
                                    {{ duration(target.response_minutes) }}
                                </td>
                                <td class="py-3 tabular-nums">
                                    {{ duration(target.resolution_minutes) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
            <div
                v-if="!slaLevels.length"
                class="bg-card rounded-xl border px-5 py-12 text-center xl:col-span-2"
            >
                <h3 class="font-semibold">Nog geen SLA-niveaus</h3>
                <p class="text-muted-foreground mt-2 text-sm">
                    Voeg een niveau toe om reactie- en oplostijden met projecten
                    af te spreken.
                </p>
            </div>
        </div>
        <ConfirmDeleteDialog
            :open="Boolean(levelToDelete)"
            @update:open="!$event && (levelToDelete = null)"
            title="SLA-niveau verwijderen"
            :description="`Wil je '${levelToDelete?.name ?? ''}' verwijderen? Dit kan niet ongedaan worden gemaakt.`"
            :processing="deleting"
            @confirm="remove"
        />
    </div>
</template>
