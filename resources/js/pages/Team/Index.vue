<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
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

const props = defineProps<{ teamMembers: TeamMember[] }>();
const editingId = ref<number | null>(null);
const editingMember = computed(
    () =>
        props.teamMembers.find((member) => member.id === editingId.value) ??
        null,
);

const form = useForm({ name: '', email: '' });

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Team', href: index() },
        ],
    },
});

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
};

const editMember = (member: TeamMember) => {
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

const deleteMember = (member: TeamMember) => {
    if (
        !window.confirm(
            `Weet je zeker dat je '${member.name}' wilt verwijderen?`,
        )
    ) {
        return;
    }

    router.delete(destroy(member.id).url, { preserveScroll: true });
};
</script>

<template>
    <Head title="Team" />

    <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4 sm:p-6">
        <section
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Team</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Beheer de medewerkers die je aan storingen kunt toewijzen.
                </p>
            </div>
        </section>

        <Card class="overflow-hidden">
            <CardHeader class="border-b">
                <CardTitle>{{
                    editingMember ? 'Teamlid bewerken' : 'Teamlid toevoegen'
                }}</CardTitle>
                <CardDescription>
                    Voeg alleen de gegevens toe die nodig zijn om iemand bij een
                    storing te herkennen.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form
                    class="grid gap-5 sm:grid-cols-2"
                    @submit.prevent="submit"
                >
                    <div class="grid gap-2">
                        <Label for="name">Naam</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            placeholder="Bijvoorbeeld: Sam Jansen"
                        />
                        <InputError :message="form.errors.name" />
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
                        />
                        <InputError :message="form.errors.email" />
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
                            v-if="editingMember"
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
                <CardTitle>Teamleden</CardTitle>
                <CardDescription
                    >Deze personen zijn beschikbaar als verantwoordelijke op een
                    storing.</CardDescription
                >
            </CardHeader>
            <CardContent class="p-0">
                <div v-if="teamMembers.length" class="divide-y">
                    <article
                        v-for="member in teamMembers"
                        :key="member.id"
                        class="hover:bg-muted/50 flex flex-col gap-4 p-4 transition-colors sm:flex-row sm:items-center sm:justify-between sm:p-5"
                    >
                        <div class="min-w-0">
                            <p class="font-medium">{{ member.name }}</p>
                            <p
                                v-if="member.email"
                                class="text-muted-foreground mt-1 text-sm"
                            >
                                {{ member.email }}
                            </p>
                            <p class="text-muted-foreground mt-1 text-sm">
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
                                size="sm"
                                variant="outline"
                                @click="editMember(member)"
                                ><Pencil /> Bewerken</Button
                            >
                            <Button
                                size="sm"
                                variant="outline"
                                @click="deleteMember(member)"
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
                    <p class="text-muted-foreground mt-1 text-sm">
                        Voeg je eerste teamlid toe om storingen toe te wijzen.
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
