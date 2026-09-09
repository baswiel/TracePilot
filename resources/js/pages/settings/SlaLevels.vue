<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
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
    priority: 'p1' | 'p2' | 'p3' | 'p4';
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
const remove = (level: SlaLevel) => {
    if (window.confirm(`Wil je SLA-niveau '${level.name}' verwijderen?`))
        router.delete(destroy(level.id).url);
};
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
                class="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl"
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

                <form class="space-y-5" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="sla-level-name">Naam</Label>
                        <Input
                            id="sla-level-name"
                            v-model="form.name"
                            required
                            placeholder="Bijvoorbeeld: Gold"
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="overflow-x-auto rounded-lg border">
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

        <div class="space-y-3">
            <div
                v-for="level in slaLevels"
                :key="level.id"
                class="flex items-center justify-between gap-3 rounded-lg border p-3"
            >
                <span class="font-medium">{{ level.name }}</span
                ><span class="flex gap-2"
                    ><Button size="sm" variant="outline" @click="edit(level)"
                        ><Pencil /> Bewerken</Button
                    ><Button size="sm" variant="outline" @click="remove(level)"
                        ><Trash2 /> Verwijderen</Button
                    ></span
                >
            </div>
            <p v-if="!slaLevels.length" class="text-muted-foreground text-sm">
                Nog geen SLA-niveaus ingesteld.
            </p>
        </div>
    </div>
</template>
