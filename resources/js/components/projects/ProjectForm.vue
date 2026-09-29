<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store, update } from '@/routes/projects';

type Project = {
    id: number;
    name: string;
    customer_id: number | null;
    description: string | null;
    sla_level_id: number | null;
    sla_first_response_minutes: number | null;
    sla_resolution_minutes: number | null;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    first_responder_id: number | null;
    second_responder_id: number | null;
    third_responder_id: number | null;
    is_active: boolean;
};

type TeamMember = {
    id: number;
    name: string;
    email: string | null;
};

type SlaLevel = { id: number; name: string };
type Customer = { id: number; name: string };

const props = defineProps<{
    project?: Project;
    teamMembers: TeamMember[];
    slaLevels: SlaLevel[];
    customers: Customer[];
    cancelHref: string;
}>();

const form = useForm({
    name: props.project?.name ?? '',
    customer_id: props.project?.customer_id ?? null,
    description: props.project?.description ?? '',
    sla_level_id: props.project?.sla_level_id ?? null,
    sla_first_response_minutes: props.project?.sla_first_response_minutes ?? '',
    sla_resolution_minutes: props.project?.sla_resolution_minutes ?? '',
    contact_name: props.project?.contact_name ?? '',
    contact_email: props.project?.contact_email ?? '',
    contact_phone: props.project?.contact_phone ?? '',
    first_responder_id: props.project?.first_responder_id ?? null,
    second_responder_id: props.project?.second_responder_id ?? null,
    third_responder_id: props.project?.third_responder_id ?? null,
    is_active: props.project?.is_active ?? true,
});

const submit = () => {
    if (props.project) {
        form.put(update(props.project.id).url);

        return;
    }

    form.post(store.url());
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="name">Projectnaam</Label>
            <Input id="name" v-model="form.name" required autofocus />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="customer_id">Klant</Label>
            <select
                id="customer_id"
                v-model="form.customer_id"
                class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
            >
                <option :value="null">Geen klant gekoppeld</option>
                <option
                    v-for="customer in customers"
                    :key="customer.id"
                    :value="customer.id"
                >
                    {{ customer.name }}
                </option>
            </select>
            <InputError :message="form.errors.customer_id" />
        </div>

        <div class="grid gap-2">
            <Label for="description">Omschrijving</Label>
            <textarea
                id="description"
                v-model="form.description"
                rows="5"
                class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
            />
            <InputError :message="form.errors.description" />
        </div>

        <fieldset class="space-y-4 rounded-lg border p-4">
            <div>
                <legend class="text-sm font-medium">SLA</legend>
                <p class="text-muted-foreground mt-1 text-sm">
                    Kies het SLA-niveau met de afgesproken reactie- en
                    oplostijden.
                </p>
            </div>
            <div v-if="slaLevels.length" class="grid gap-2">
                <Label for="sla_level_id">SLA-niveau</Label>
                <select
                    id="sla_level_id"
                    v-model="form.sla_level_id"
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                >
                    <option :value="null">Geen SLA-niveau</option>
                    <option
                        v-for="slaLevel in slaLevels"
                        :key="slaLevel.id"
                        :value="slaLevel.id"
                    >
                        {{ slaLevel.name }}
                    </option>
                </select>
                <InputError :message="form.errors.sla_level_id" />
            </div>
            <p v-else class="text-muted-foreground text-sm">
                Maak eerst een SLA-niveau aan in Instellingen.
            </p>
        </fieldset>

        <fieldset class="space-y-4 rounded-lg border p-4">
            <div>
                <legend class="text-sm font-medium">Contactpersoon</legend>
                <p class="text-muted-foreground mt-1 text-sm">
                    Dit is het aanspreekpunt bij dit project.
                </p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="contact_name">Naam</Label>
                    <Input id="contact_name" v-model="form.contact_name" />
                    <InputError :message="form.errors.contact_name" />
                </div>
                <div class="grid gap-2">
                    <Label for="contact_email">E-mailadres</Label>
                    <Input
                        id="contact_email"
                        v-model="form.contact_email"
                        type="email"
                    />
                    <InputError :message="form.errors.contact_email" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="contact_phone">Telefoonnummer</Label>
                    <Input id="contact_phone" v-model="form.contact_phone" />
                    <InputError :message="form.errors.contact_phone" />
                </div>
            </div>
        </fieldset>

        <fieldset class="space-y-4 rounded-lg border p-4">
            <div>
                <legend class="text-sm font-medium">Responders</legend>
                <p class="text-muted-foreground mt-1 text-sm">
                    Wijs de drie vaste responders voor dit project toe.
                </p>
            </div>
            <div
                v-if="teamMembers.length >= 3"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <div class="grid gap-2">
                    <Label for="first_responder_id">Eerste responder</Label>
                    <select
                        id="first_responder_id"
                        v-model="form.first_responder_id"
                        required
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    >
                        <option :value="null" disabled>Kies een teamlid</option>
                        <option
                            v-for="teamMember in teamMembers"
                            :key="teamMember.id"
                            :value="teamMember.id"
                        >
                            {{ teamMember.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.first_responder_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="second_responder_id">Tweede responder</Label>
                    <select
                        id="second_responder_id"
                        v-model="form.second_responder_id"
                        required
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    >
                        <option :value="null" disabled>Kies een teamlid</option>
                        <option
                            v-for="teamMember in teamMembers"
                            :key="teamMember.id"
                            :value="teamMember.id"
                            :disabled="
                                teamMember.id === form.first_responder_id
                            "
                        >
                            {{ teamMember.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.second_responder_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="third_responder_id">Derde responder</Label>
                    <select
                        id="third_responder_id"
                        v-model="form.third_responder_id"
                        required
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    >
                        <option :value="null" disabled>Kies een teamlid</option>
                        <option
                            v-for="teamMember in teamMembers"
                            :key="teamMember.id"
                            :value="teamMember.id"
                            :disabled="
                                teamMember.id === form.first_responder_id ||
                                teamMember.id === form.second_responder_id
                            "
                        >
                            {{ teamMember.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.third_responder_id" />
                </div>
            </div>
            <p v-else class="text-muted-foreground text-sm">
                Voeg eerst minstens drie teamleden toe om responders toe te
                wijzen.
            </p>
        </fieldset>

        <label class="flex items-center gap-3 rounded-lg border p-4">
            <input
                v-model="form.is_active"
                type="checkbox"
                class="accent-primary size-4"
            />
            <span>
                <span class="block text-sm font-medium">Actief project</span>
                <span class="text-muted-foreground block text-sm">
                    Inactieve projecten blijven bewaard, maar zijn gearchiveerd.
                </span>
            </span>
        </label>
        <InputError :message="form.errors.is_active" />

        <div class="flex flex-wrap gap-3">
            <Button :disabled="form.processing" type="submit">
                {{ project ? 'Wijzigingen opslaan' : 'Project toevoegen' }}
            </Button>
            <Button variant="outline" as-child>
                <Link :href="cancelHref">Annuleren</Link>
            </Button>
        </div>
    </form>
</template>
