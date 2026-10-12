<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { useSubmissionFeedback } from '@/composables/useSubmissionFeedback';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/business-hours';

type BusinessHours = {
    working_days: number[];
    starts_at: string;
    ends_at: string;
};

const props = defineProps<{ businessHours: BusinessHours }>();
const days = [
    { value: 1, label: 'Maandag' },
    { value: 2, label: 'Dinsdag' },
    { value: 3, label: 'Woensdag' },
    { value: 4, label: 'Donderdag' },
    { value: 5, label: 'Vrijdag' },
    { value: 6, label: 'Zaterdag' },
    { value: 7, label: 'Zondag' },
];
const form = useForm({ ...props.businessHours });

const formElement = ref<HTMLFormElement>();
const { submissionError, failureCallbacks } = useSubmissionFeedback(
    'Controleer de opgeslagen werkuren voordat je opnieuw probeert.',
);
const submit = () => {
    if (form.processing) return;
    submissionError.value = '';
    form.patch(update.url(), {
        ...failureCallbacks,
        onFinish: async () => {
            await nextTick();
            const target = formElement.value?.querySelector<HTMLElement>(
                submissionError.value
                    ? '[role="alert"]'
                    : '[aria-invalid="true"]',
            );
            target?.focus();
            target?.scrollIntoView({ block: 'center', behavior: 'instant' });
        },
    });
};

const toggleDay = (day: number, checked: boolean) => {
    form.working_days = checked
        ? [...form.working_days, day].sort((a, b) => a - b)
        : form.working_days.filter((value) => value !== day);
};
</script>

<template>
    <Head title="Werkuren" />

    <div class="space-y-8">
        <div>
            <h2 class="text-lg font-medium">Werkuren</h2>
            <p class="text-muted-foreground mt-1 text-sm">
                SLA-tijden lopen alleen door binnen deze werkdagen en uren.
            </p>
        </div>

        <form
            ref="formElement"
            :aria-busy="form.processing"
            class="bg-card max-w-3xl space-y-6 rounded-xl border p-5 sm:p-6"
            @submit.prevent="submit"
        >
            <p
                v-if="submissionError"
                role="alert"
                tabindex="-1"
                class="text-destructive text-sm wrap-anywhere"
            >
                {{ submissionError }}
            </p>
            <fieldset :disabled="form.processing" class="min-w-0 space-y-6">
                <fieldset class="space-y-3">
                    <legend class="text-sm font-medium">Werkdagen</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <Label
                            v-for="day in days"
                            :key="day.value"
                            :for="`working-day-${day.value}`"
                            class="flex items-center gap-3 rounded-lg border p-3 font-normal transition-colors"
                            :class="
                                form.working_days.includes(day.value)
                                    ? 'border-primary bg-primary/5'
                                    : 'border-border hover:bg-muted/50'
                            "
                        >
                            <Checkbox
                                :id="`working-day-${day.value}`"
                                :model-value="
                                    form.working_days.includes(day.value)
                                "
                                :aria-invalid="
                                    Boolean(form.errors.working_days)
                                "
                                aria-describedby="hours-working_days-error"
                                @update:model-value="
                                    toggleDay(day.value, $event === true)
                                "
                            />
                            {{ day.label }}
                        </Label>
                    </div>
                    <InputError
                        id="hours-working_days-error"
                        :message="form.errors.working_days"
                    />
                </fieldset>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="business-hours-start">Van</Label>
                        <Input
                            id="business-hours-start"
                            v-model="form.starts_at"
                            type="time"
                            required
                            :aria-invalid="Boolean(form.errors.starts_at)"
                            aria-describedby="hours-starts_at-error"
                        />
                        <InputError
                            id="hours-starts_at-error"
                            :message="form.errors.starts_at"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="business-hours-end">Tot</Label>
                        <Input
                            id="business-hours-end"
                            v-model="form.ends_at"
                            type="time"
                            required
                            :aria-invalid="Boolean(form.errors.ends_at)"
                            aria-describedby="hours-ends_at-error"
                        />
                        <InputError
                            id="hours-ends_at-error"
                            :message="form.errors.ends_at"
                        />
                    </div>
                </div>

                <p class="text-muted-foreground text-sm">
                    Voorbeeld: een melding op vrijdag om 16:55 met een
                    reactietijd van 30 minuten verloopt op maandag om 09:25.
                </p>

                <Button
                    class="h-11 w-full sm:h-10 sm:w-auto"
                    :disabled="form.processing"
                    type="submit"
                    >{{
                        form.processing
                            ? 'Bezig met opslaan…'
                            : 'Werkuren opslaan'
                    }}</Button
                >
            </fieldset>
        </form>
    </div>
</template>
