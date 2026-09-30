<script setup lang="ts">
import { computed } from 'vue';

type Milestone = {
    state:
        | 'unavailable'
        | 'on_track'
        | 'at_risk'
        | 'overdue'
        | 'met'
        | 'breached';
    label: string;
    remaining_minutes: number | null;
};

const props = defineProps<{ response: Milestone; resolution: Milestone }>();

const milestone = computed(
    () =>
        [props.response, props.resolution].find((item) =>
            ['overdue', 'breached', 'at_risk'].includes(item.state),
        ) ?? props.response,
);

const display = computed(() => {
    const minutes = milestone.value.remaining_minutes;
    const absolute = minutes === null ? null : Math.abs(minutes);
    const duration =
        absolute === null
            ? 'Niet beschikbaar'
            : absolute < 60
              ? `${absolute} min`
              : `${Math.floor(absolute / 60)} u ${absolute % 60} min`;

    return {
        class: {
            unavailable: 'border-slate-200 bg-slate-50 text-slate-700',
            on_track: 'border-emerald-200 bg-emerald-50 text-emerald-700',
            at_risk: 'border-orange-200 bg-orange-50 text-orange-700',
            overdue: 'border-red-200 bg-red-50 text-red-700',
            met: 'border-emerald-200 bg-emerald-50 text-emerald-700',
            breached: 'border-red-200 bg-red-50 text-red-700',
        }[milestone.value.state],
        label: milestone.value.label,
        time:
            minutes === null
                ? duration
                : minutes < 0
                  ? `${duration} te laat`
                  : `${duration} resterend`,
    };
});
</script>

<template>
    <span
        class="inline-flex flex-col rounded-lg border px-2.5 py-1 text-xs font-medium"
        :class="display.class"
    >
        <span>{{ display.label }}</span>
        <span class="mt-0.5 font-normal opacity-80">{{ display.time }}</span>
    </span>
</template>
