<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';
/* @chisel-passkeys */
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
/* @end-chisel-passkeys */
/* @chisel-2fa */
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
/* @end-chisel-2fa */

// oxfmt-ignore
type Props = {
    passwordRules: string;
} /* @chisel-passkeys */ & ManagePasskeysProps /* @end-chisel-passkeys */ /* @chisel-2fa */ &
    ManageTwoFactorProps /* @end-chisel-2fa */;

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Beveiliging',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Beveiliging" />

    <div class="bg-card max-w-3xl space-y-6 rounded-xl border p-5 sm:p-6">
        <Heading
            variant="small"
            title="Wachtwoord wijzigen"
            description="Gebruik een lang, uniek wachtwoord om je account te beveiligen."
        />

        <Form
            v-bind="SecurityController.update.form()"
            :options="{
                preserveScroll: true,
            }"
            reset-on-success
            :reset-on-error="[
                'password',
                'password_confirmation',
                'current_password',
            ]"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="current_password">Huidig wachtwoord</Label>
                <PasswordInput
                    id="current_password"
                    :aria-invalid="Boolean(errors.current_password)"
                    aria-describedby="account-current_password-error"
                    name="current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="Huidig wachtwoord"
                />
                <InputError
                    id="account-current_password-error"
                    :message="errors.current_password"
                />
            </div>

            <div class="grid gap-2">
                <Label for="password">Nieuw wachtwoord</Label>
                <PasswordInput
                    id="password"
                    :aria-invalid="Boolean(errors.password)"
                    aria-describedby="account-password-error"
                    name="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Nieuw wachtwoord"
                    :passwordrules="props.passwordRules"
                />
                <InputError
                    id="account-password-error"
                    :message="errors.password"
                />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Wachtwoord bevestigen</Label>
                <PasswordInput
                    id="password_confirmation"
                    :aria-invalid="Boolean(errors.password_confirmation)"
                    aria-describedby="account-password_confirmation-error"
                    name="password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Wachtwoord bevestigen"
                    :passwordrules="props.passwordRules"
                />
                <InputError
                    id="account-password_confirmation-error"
                    :message="errors.password_confirmation"
                />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-password-button"
                    >Opslaan</Button
                >
            </div>
        </Form>
    </div>

    <!-- @chisel-2fa -->
    <ManageTwoFactor
        :canManageTwoFactor="canManageTwoFactor"
        :requiresConfirmation="requiresConfirmation"
        :twoFactorEnabled="twoFactorEnabled"
    />
    <!-- @end-chisel-2fa -->

    <!-- @chisel-passkeys -->
    <ManagePasskeys
        :canManagePasskeys="canManagePasskeys"
        :passkeys="passkeys"
    />
    <!-- @end-chisel-passkeys -->
</template>
