<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Wachtwoord herstellen',
        description: 'Kies een nieuw wachtwoord voor je account.',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <Head title="Wachtwoord herstellen" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">E-mailadres</Label>
                <Input
                    id="email"
                    :aria-invalid="Boolean(errors.email)"
                    aria-describedby="account-email-error"
                    type="email"
                    name="email"
                    autocomplete="email"
                    v-model="inputEmail"
                    class="mt-1 block w-full"
                    readonly
                />
                <InputError
                    id="account-email-error"
                    :message="errors.email"
                    class="mt-2"
                />
            </div>

            <div class="grid gap-2">
                <Label for="password">Wachtwoord</Label>
                <PasswordInput
                    id="password"
                    :aria-invalid="Boolean(errors.password)"
                    aria-describedby="account-password-error"
                    name="password"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    autofocus
                    placeholder="Wachtwoord"
                    :passwordrules="passwordRules"
                />
                <InputError
                    id="account-password-error"
                    :message="errors.password"
                />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">
                    Wachtwoord bevestigen
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    :aria-invalid="Boolean(errors.password_confirmation)"
                    aria-describedby="account-password_confirmation-error"
                    name="password_confirmation"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    placeholder="Wachtwoord bevestigen"
                    :passwordrules="passwordRules"
                />
                <InputError
                    id="account-password_confirmation-error"
                    :message="errors.password_confirmation"
                />
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :disabled="processing"
                data-test="reset-password-button"
            >
                <Spinner v-if="processing" />
                Wachtwoord herstellen
            </Button>
        </div>
    </Form>
</template>
