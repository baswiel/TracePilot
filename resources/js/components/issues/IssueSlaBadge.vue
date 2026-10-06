<script setup lang="ts">
import { slaTone } from '@/lib/issues';
import type { SlaMilestone } from '@/types/issues';
import { computed } from 'vue';

type Milestone = Pick<SlaMilestone, 'state' | 'label' | 'remaining_minutes'>;

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
        class: slaTone(milestone.value.state),
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
