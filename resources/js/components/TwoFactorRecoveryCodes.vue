<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Eye, EyeOff, LockKeyhole, RefreshCw } from '@lucide/vue';
import { nextTick, onMounted, ref, useTemplateRef } from 'vue';
import AlertError from '@/components/AlertError.vue';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { regenerateRecoveryCodes } from '@/routes/two-factor';

const { recoveryCodesList, fetchRecoveryCodes, errors } = useTwoFactorAuth();
const isRecoveryCodesVisible = ref<boolean>(false);
const recoveryCodeSectionRef = useTemplateRef('recoveryCodeSectionRef');

const toggleRecoveryCodesVisibility = async () => {
    if (!isRecoveryCodesVisible.value && !recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }

    isRecoveryCodesVisible.value = !isRecoveryCodesVisible.value;

    if (isRecoveryCodesVisible.value) {
        await nextTick();
        recoveryCodeSectionRef.value?.scrollIntoView({
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)')
                .matches
                ? 'instant'
                : 'smooth',
        });
    }
};

onMounted(async () => {
    if (!recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }
});
</script>

<template>
    <section class="w-full space-y-4 border-t pt-6">
        <header class="space-y-2">
            <h3 class="flex items-center gap-2 font-semibold">
                <LockKeyhole class="size-4" />Herstelcodes
            </h3>
            <p class="text-muted-foreground text-sm">
                Met herstelcodes krijg je weer toegang als je je authenticator
                niet kunt gebruiken. Bewaar ze in een veilige wachtwoordmanager.
            </p>
        </header>
        <div class="t-acc" :data-open="isRecoveryCodesVisible">
            <div
                class="flex flex-col gap-3 select-none sm:flex-row sm:items-center sm:justify-between"
            >
                <Button
                    @click="toggleRecoveryCodesVisibility"
                    class="w-fit"
                    :aria-expanded="isRecoveryCodesVisible"
                    aria-controls="recovery-codes-panel"
                >
                    <component
                        :is="isRecoveryCodesVisible ? EyeOff : Eye"
                        class="size-4"
                    />
                    {{
                        isRecoveryCodesVisible
                            ? 'Herstelcodes verbergen'
                            : 'Herstelcodes bekijken'
                    }}
                </Button>

                <Form
                    v-if="isRecoveryCodesVisible && recoveryCodesList.length"
                    v-bind="regenerateRecoveryCodes.form()"
                    method="post"
                    :options="{ preserveScroll: true }"
                    @success="fetchRecoveryCodes"
                    #default="{ processing }"
                >
                    <Button
                        variant="secondary"
                        type="submit"
                        :disabled="processing"
                    >
                        <RefreshCw /> Nieuwe codes maken
                    </Button>
                </Form>
            </div>
            <div
                id="recovery-codes-panel"
                class="t-acc-panel"
                :inert="!isRecoveryCodesVisible"
                :aria-hidden="!isRecoveryCodesVisible"
            >
                <div class="t-acc-panel-inner">
                    <div v-if="errors?.length" class="mt-6">
                        <AlertError :errors="errors" />
                    </div>
                    <div v-else class="mt-3 space-y-3">
                        <div
                            ref="recoveryCodeSectionRef"
                            class="bg-muted grid gap-1 rounded-lg p-4 font-mono text-sm"
                        >
                            <div
                                v-if="!recoveryCodesList.length"
                                class="space-y-2"
                            >
                                <div
                                    v-for="n in 8"
                                    :key="n"
                                    class="bg-muted-foreground/20 h-4 animate-pulse rounded motion-reduce:animate-none"
                                ></div>
                            </div>
                            <div
                                v-else
                                v-for="(code, index) in recoveryCodesList"
                                :key="index"
                            >
                                {{ code }}
                            </div>
                        </div>
                        <p class="text-muted-foreground text-xs select-none">
                            Elke herstelcode is één keer bruikbaar. Kies
                            <span class="font-medium">Nieuwe codes maken</span>
                            als je nieuwe codes nodig hebt.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
