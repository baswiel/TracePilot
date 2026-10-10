<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Account aanmaken',
        description: 'Start met het bewaken van je projecten en SLA-afspraken.',
    },
});
</script>

<template>
    <Head title="Account aanmaken" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Naam</Label>
                <Input
                    id="name"
                    :aria-invalid="Boolean(errors.name)"
                    aria-describedby="account-name-error"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    name="name"
                    placeholder="Je volledige naam"
                />
                <InputError id="account-name-error" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">E-mailadres</Label>
                <Input
                    id="email"
                    :aria-invalid="Boolean(errors.email)"
                    aria-describedby="account-email-error"
                    type="email"
                    required
                    autocomplete="email"
                    name="email"
                    placeholder="naam@bedrijf.nl"
                />
                <InputError id="account-email-error" :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Wachtwoord</Label>
                <PasswordInput
                    id="password"
                    :aria-invalid="Boolean(errors.password)"
                    aria-describedby="account-password-error"
                    required
                    autocomplete="new-password"
                    name="password"
                    placeholder="Kies een sterk wachtwoord"
                    :passwordrules="passwordRules"
                />
                <InputError
                    id="account-password-error"
                    :message="errors.password"
                />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Bevestig wachtwoord</Label>
                <PasswordInput
                    id="password_confirmation"
                    :aria-invalid="Boolean(errors.password_confirmation)"
                    aria-describedby="account-password_confirmation-error"
                    required
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Herhaal je wachtwoord"
                    :passwordrules="passwordRules"
                />
                <InputError
                    id="account-password_confirmation-error"
                    :message="errors.password_confirmation"
                />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Account aanmaken
            </Button>
        </div>

        <div class="text-muted-foreground text-center text-sm">
            Al een account?
            <TextLink :href="login()" class="underline underline-offset-4"
                >Inloggen</TextLink
            >
        </div>
    </Form>
</template>
