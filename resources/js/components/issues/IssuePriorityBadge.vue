<script setup lang="ts">
import type { IssuePriority } from '@/types/issues';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    priority: IssuePriority;
}>();

const display = computed(() => {
    const labels = {
        p1: {
            label: 'P1 Kritiek',
            class: 'border-red-200 bg-red-50 text-[var(--tracepilot-danger)]',
        },
        p2: {
            label: 'P2 Hoog',
            class: 'border-orange-200 bg-orange-50 text-[var(--tracepilot-warning)]',
        },
        p3: {
            label: 'P3 Normaal',
            class: 'border-slate-300 bg-slate-50 text-slate-600',
        },
        p4: {
            label: 'P4 Laag',
            class: 'border-slate-200 bg-white text-slate-500',
        },
    } as const;

    return labels[props.priority];
});
</script>

<template>
    <Badge :class="display.class" variant="outline">{{ display.label }}</Badge>
</template>
