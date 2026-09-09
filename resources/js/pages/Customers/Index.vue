<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
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
import { destroy, index, store, update } from '@/routes/customers';

type Customer = { id: number; name: string; projects_count: number };
const props = defineProps<{ customers: Customer[] }>();
const editingId = ref<number | null>(null);
const editingCustomer = computed(
    () =>
        props.customers.find((customer) => customer.id === editingId.value) ??
        null,
);
const form = useForm({ name: '' });
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Klanten', href: index() },
        ],
    },
});
const reset = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
};
const edit = (customer: Customer) => {
    editingId.value = customer.id;
    form.name = customer.name;
    form.clearErrors();
};
const submit = () =>
    editingCustomer.value
        ? form.patch(update(editingCustomer.value.id).url, { onSuccess: reset })
        : form.post(store.url(), { onSuccess: reset });
const remove = (customer: Customer) => {
    if (window.confirm(`Wil je '${customer.name}' verwijderen?`))
        router.delete(destroy(customer.id).url);
};
</script>
<template>
    <Head title="Klanten" />

    <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4 sm:p-6">
        <section
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Klanten</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Beheer klanten en koppel ze aan projecten.
                </p>
            </div>
        </section>

        <Card>
            <CardHeader>
                <CardTitle>{{
                    editingCustomer ? 'Klant bewerken' : 'Klant toevoegen'
                }}</CardTitle>
                <CardDescription
                    >Een klant kan aan meerdere projecten gekoppeld
                    worden.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <form
                    class="flex flex-col gap-4 sm:flex-row sm:items-end"
                    @submit.prevent="submit"
                >
                    <div class="grid flex-1 gap-2">
                        <Label for="name">Naam</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            required
                            placeholder="Bijvoorbeeld: Acme B.V."
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="flex gap-2">
                        <Button type="submit"
                            ><Plus v-if="!editingCustomer" />{{
                                editingCustomer ? 'Opslaan' : 'Toevoegen'
                            }}</Button
                        >
                        <Button
                            v-if="editingCustomer"
                            type="button"
                            variant="outline"
                            @click="reset"
                            >Annuleren</Button
                        >
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card class="overflow-hidden">
            <CardHeader class="border-b"
                ><CardTitle>Klantenlijst</CardTitle></CardHeader
            >
            <CardContent class="p-0">
                <div v-if="customers.length" class="divide-y">
                    <div
                        v-for="customer in customers"
                        :key="customer.id"
                        class="hover:bg-muted/50 flex items-center justify-between gap-3 p-4 transition-colors sm:p-5"
                    >
                        <div>
                            <p class="font-medium">{{ customer.name }}</p>
                            <p class="text-muted-foreground text-sm">
                                {{ customer.projects_count }}
                                {{
                                    customer.projects_count === 1
                                        ? 'project'
                                        : 'projecten'
                                }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="edit(customer)"
                                ><Pencil /> Bewerken</Button
                            >
                            <Button
                                size="sm"
                                variant="outline"
                                :disabled="customer.projects_count > 0"
                                @click="remove(customer)"
                                ><Trash2 /> Verwijderen</Button
                            >
                        </div>
                    </div>
                </div>
                <div
                    v-else
                    class="flex flex-col items-center gap-3 px-6 py-14 text-center"
                >
                    <p class="font-medium">Nog geen klanten</p>
                    <p class="text-muted-foreground text-sm">
                        Voeg de eerste klant toe om projecten te kunnen
                        koppelen.
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
