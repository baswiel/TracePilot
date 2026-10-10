<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
/* @chisel-email-verification */
import { Link } from '@inertiajs/vue3';
/* @end-chisel-email-verification */
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
/* @chisel-email-verification */
import { send } from '@/routes/verification';
/* @end-chisel-email-verification */

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profiel',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Profiel" />

    <div
        class="bg-card flex max-w-3xl flex-col space-y-6 rounded-xl border p-5 sm:p-6"
    >
        <Heading
            variant="small"
            title="Profiel"
            description="Werk je naam en e-mailadres bij."
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Naam</Label>
                <Input
                    id="name"
                    :aria-invalid="Boolean(errors.name)"
                    aria-describedby="account-name-error"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Volledige naam"
                />
                <InputError
                    class="mt-2"
                    id="account-name-error"
                    :message="errors.name"
                />
            </div>

            <div class="grid gap-2">
                <Label for="email">E-mailadres</Label>
                <Input
                    id="email"
                    :aria-invalid="Boolean(errors.email)"
                    aria-describedby="account-email-error"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="E-mailadres"
                />
                <InputError
                    class="mt-2"
                    id="account-email-error"
                    :message="errors.email"
                />
            </div>

            <!-- @chisel-email-verification -->
            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="text-muted-foreground -mt-4 text-sm">
                    Je e-mailadres is nog niet bevestigd.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Verstuur de verificatiemail opnieuw.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-[var(--tracepilot-success-ink)]"
                >
                    Er is een nieuwe verificatielink naar je e-mailadres
                    verzonden.
                </div>
            </div>
            <!-- @end-chisel-email-verification -->

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Opslaan</Button
                >
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
