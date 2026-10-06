<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
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

        <form class="space-y-6" @submit.prevent="form.patch(update.url())">
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
                            :checked="form.working_days.includes(day.value)"
                            @update:checked="
                                toggleDay(day.value, $event === true)
                            "
                        />
                        {{ day.label }}
                    </Label>
                </div>
                <InputError :message="form.errors.working_days" />
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
                Voorbeeld: een melding op vrijdag om 16:55 met een reactietijd
                van 30 minuten verloopt op maandag om 09:25.
            </p>

            <Button :disabled="form.processing" type="submit"
                >Werkuren opslaan</Button
            >
        </form>
    </div>
</template>
