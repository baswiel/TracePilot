<script setup lang="ts">
import WorkspaceHeader from '@/components/WorkspaceHeader.vue';
import WorkspacePage from '@/components/WorkspacePage.vue';
import PagePagination from '@/components/PagePagination.vue';
import type { Paginated } from '@/types/pagination';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, Users } from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import ConfirmDeleteDialog from '@/components/ConfirmDeleteDialog.vue';
import InputError from '@/components/InputError.vue';
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
import { destroy, index, store, update } from '@/routes/team';

type TeamMember = {
    id: number;
    name: string;
    email: string | null;
    assigned_issues_count: number;
};

const props = defineProps<{ teamMembers: Paginated<TeamMember> }>();
const formOpen = ref(false);
const focusEditor = async () => {
    await nextTick();
    document.getElementById('name')?.focus();
};
const openCreate = () => {
    resetForm();
    formOpen.value = true;
    void focusEditor();
};
const editingId = ref<number | null>(null);
const editingMember = computed(
    () =>
        props.teamMembers.data.find(
            (member) => member.id === editingId.value,
        ) ?? null,
);

const form = useForm({ name: '', email: '' });
const memberToDelete = ref<TeamMember | null>(null);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Team', href: index() },
        ],
    },
});

const resetForm = () => {
    formOpen.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
};

const editMember = (member: TeamMember) => {
    formOpen.value = true;
    void focusEditor();
    editingId.value = member.id;
    form.name = member.name;
    form.email = member.email ?? '';
    form.clearErrors();
};

const submit = () => {
    if (editingMember.value) {
        form.patch(update(editingMember.value.id).url, {
            onSuccess: resetForm,
        });

        return;
    }

    form.post(store.url(), { onSuccess: resetForm });
};

const deleteMember = () => {
    if (!memberToDelete.value) return;
    router.delete(destroy(memberToDelete.value.id).url, {
        preserveScroll: true,
        onSuccess: () => (memberToDelete.value = null),
    });
};
</script>

<template>
    <Head title="Team" />

    <WorkspacePage>
        <WorkspaceHeader
            title="Team"
            description="Beheer de medewerkers die je aan storingen kunt toewijzen."
            ><Button
                :aria-expanded="formOpen"
                aria-controls="team-editor"
                @click="openCreate"
                ><Plus /> Teamlid toevoegen</Button
            ></WorkspaceHeader
        >

        <Card
            v-if="formOpen"
            id="team-editor"
            class="gap-0 overflow-hidden py-0"
        >
            <CardHeader class="border-b py-5">
                <CardTitle as="h2">{{
                    editingMember ? 'Teamlid bewerken' : 'Teamlid toevoegen'
                }}</CardTitle>
                <CardDescription>
                    Voeg alleen de gegevens toe die nodig zijn om iemand bij een
                    storing te herkennen.
                </CardDescription>
            </CardHeader>
            <CardContent class="p-5 sm:p-6">
                <form
                    class="grid gap-5 sm:grid-cols-2"
                    @submit.prevent="submit"
                >
                    <div class="grid gap-2">
                        <Label for="name">Naam (verplicht)</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            placeholder="Bijvoorbeeld: Sam Jansen"
                            :aria-invalid="Boolean(form.errors.name)"
                            aria-describedby="team-name-error"
                        />
                        <InputError
                            id="team-name-error"
                            :message="form.errors.name"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email"
                            >E-mailadres
                            <span class="text-muted-foreground"
                                >(optioneel)</span
                            ></Label
                        >
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="sam@bedrijf.nl"
                            :aria-invalid="Boolean(form.errors.email)"
                            aria-describedby="team-email-error"
                        />
                        <InputError
                            id="team-email-error"
                            :message="form.errors.email"
                        />
                    </div>
                    <div class="flex flex-wrap gap-3 sm:col-span-2">
                        <Button :disabled="form.processing" type="submit">
                            <Plus v-if="!editingMember" />
                            {{
                                editingMember
                                    ? 'Wijzigingen opslaan'
                                    : 'Teamlid toevoegen'
                            }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="resetForm"
                            >Annuleren</Button
                        >
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card class="overflow-hidden">
            <CardHeader class="border-b">
                <CardTitle as="h2"
                    >Teamleden
                    <span
                        class="text-muted-foreground ml-2 text-sm font-normal tabular-nums"
                        >{{ teamMembers.total }}</span
                    ></CardTitle
                >
                <CardDescription
                    >Deze personen zijn beschikbaar als verantwoordelijke op een
                    storing.</CardDescription
                >
            </CardHeader>
            <CardContent class="p-0">
                <div v-if="teamMembers.data.length" class="divide-y">
                    <article
                        v-for="member in teamMembers.data"
                        :key="member.id"
                        class="hover:bg-muted/50 flex flex-col gap-4 p-4 transition-colors sm:flex-row sm:items-center sm:justify-between sm:p-5"
                    >
                        <div class="min-w-0">
                            <p class="font-semibold break-words">
                                {{ member.name }}
                            </p>
                            <p
                                v-if="member.email"
                                class="text-muted-foreground mt-1 text-sm break-words"
                            >
                                {{ member.email }}
                            </p>
                            <p
                                class="text-muted-foreground mt-1 text-sm break-words"
                            >
                                {{ member.assigned_issues_count }}
                                {{
                                    member.assigned_issues_count === 1
                                        ? 'storing toegewezen'
                                        : 'storingen toegewezen'
                                }}
                            </p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <Button
                                size="default"
                                variant="outline"
                                @click="editMember(member)"
                                ><Pencil /> Bewerken</Button
                            >
                            <Button
                                size="default"
                                variant="outline"
                                @click="memberToDelete = member"
                                ><Trash2 /> Verwijderen</Button
                            >
                        </div>
                    </article>
                </div>
                <div
                    v-else
                    class="flex flex-col items-center gap-3 px-6 py-14 text-center"
                >
                    <Users class="text-muted-foreground size-8" />
                    <p class="mt-3 font-medium">Nog geen teamleden</p>
                    <p class="text-muted-foreground mt-1 text-sm break-words">
                        Voeg je eerste teamlid toe om storingen toe te wijzen.
                    </p>
                </div>
            </CardContent>
        </Card>
        <ConfirmDeleteDialog
            :open="Boolean(memberToDelete)"
            @update:open="
                (open) => {
                    if (!open) memberToDelete = null;
                }
            "
            title="Teamlid verwijderen"
            :description="
                memberToDelete
                    ? `Weet je zeker dat je '${memberToDelete.name}' wilt verwijderen? Openstaande toewijzingen worden verwijderd.`
                    : ''
            "
            @confirm="deleteMember"
        />
        <PagePagination :page="teamMembers" />
    </WorkspacePage>
</template>
