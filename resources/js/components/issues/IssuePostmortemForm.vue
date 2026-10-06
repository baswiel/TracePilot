<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { update as updatePostmortem } from '@/routes/issues/postmortem';
import type { Postmortem, TeamMember } from '@/types/issues';
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
    postmortem: Postmortem | null;
    teamMembers: TeamMember[];
}>();
const postmortemForm = useForm({
    root_cause: props.postmortem?.root_cause ?? '',
    impact: props.postmortem?.impact ?? '',
    action_items: (props.postmortem?.action_items ?? []).map((item) => ({
        id: item.id,
        title: item.title,
        owner_team_member_id: item.owner_team_member_id,
        due_date: item.due_date ?? '',
        is_completed: item.is_completed,
    })),
});

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
    postmortemForm.put(updatePostmortem(props.issueId).url, {
        preserveScroll: true,
    });
};
</script>
<template>
    <Card>
        <CardHeader>
            <CardTitle>Postmortem</CardTitle>
            <CardDescription>
                Leg de oorzaak, impact en verbeteracties vast om herhaling te
                voorkomen.
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
                            :aria-invalid="
                                Boolean(postmortemForm.errors.root_cause)
                            "
                            aria-describedby="postmortem_root_cause-error"
                        />
                        <InputError
                            id="postmortem_root_cause-error"
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
                            :aria-invalid="
                                Boolean(postmortemForm.errors.impact)
                            "
                            aria-describedby="postmortem_impact-error"
                        />
                        <InputError
                            id="postmortem_impact-error"
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
                                Maak verbetering concreet met een eigenaar en
                                deadline.
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
                                    v-model="actionItem.owner_team_member_id"
                                    class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
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
                                <label class="flex items-center gap-2 text-sm">
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
                                    @click="removePostmortemActionItem(index)"
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
                        Nog geen actiepunten. Voeg verbeterwerk toe om herhaling
                        te voorkomen.
                    </p>
                </section>

                <Button :disabled="postmortemForm.processing" type="submit">
                    Postmortem opslaan
                </Button>
            </form>
        </CardContent>
    </Card>
</template>
