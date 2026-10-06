<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/customers';

const props = defineProps<{
    customer: { id: number; name: string };
    cancelHref: string;
}>();

const form = useForm({ name: props.customer.name });

const submit = () => form.patch(update(props.customer.id).url);
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="name">Naam</Label>
            <Input id="name" v-model="form.name" required autofocus />
            <InputError :message="form.errors.name" />
        </div>

        <div class="flex flex-wrap gap-2">
            <Button type="submit" :disabled="form.processing"> Opslaan </Button>
            <Button variant="outline" as-child>
                <Link :href="cancelHref">Annuleren</Link>
            </Button>
        </div>
    </form>
</template>
