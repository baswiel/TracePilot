<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Wachtwoord vergeten',
        description: 'Vul je e-mailadres in om een herstellink te ontvangen.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Wachtwoord vergeten" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-[var(--tracepilot-success-ink)]"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email">E-mailadres</Label>
                <Input
                    id="email"
                    :aria-invalid="Boolean(errors.email)"
                    aria-describedby="account-email-error"
                    type="email"
                    name="email"
                    autocomplete="off"
                    autofocus
                    placeholder="naam@bedrijf.nl"
                />
                <InputError id="account-email-error" :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="w-full"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    Herstellink versturen
                </Button>
            </div>
        </Form>

        <div class="text-muted-foreground space-x-1 text-center text-sm">
            <span>Terug naar</span>
            <TextLink :href="login()">inloggen</TextLink>
        </div>
    </div>
</template>
