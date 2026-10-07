<script setup lang="ts">
import type { IssueStatus } from '@/types/issues';
import { computed } from 'vue';

const props = defineProps<{
    status: IssueStatus;
}>();

const display = computed(() => {
    const labels = {
        open: { label: 'Open', class: 'bg-[var(--tracepilot-danger)]' },
        handling: {
            label: 'Afhandeling',
            class: 'bg-[var(--tracepilot-warning)]',
        },
        completed: {
            label: 'Afgerond',
            class: 'bg-[var(--tracepilot-success)]',
        },
    } as const;

    return labels[props.status];
});
</script>

<template>
    <span class="inline-flex items-center gap-2 text-sm text-foreground">
        <span class="size-2 rounded-full" :class="display.class" />
        {{ display.label }}
    </span>
</template>
