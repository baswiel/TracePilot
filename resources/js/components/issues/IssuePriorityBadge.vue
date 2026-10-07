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
            class: 'tone-danger',
        },
        p2: {
            label: 'P2 Hoog',
            class: 'tone-warning',
        },
        p3: {
            label: 'P3 Normaal',
            class: 'border-border bg-muted text-foreground',
        },
        p4: {
            label: 'P4 Laag',
            class: 'border-border bg-card text-muted-foreground',
        },
    } as const;

    return labels[props.priority];
});
</script>

<template>
    <Badge :class="display.class" variant="outline">{{ display.label }}</Badge>
</template>
