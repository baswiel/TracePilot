<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        dark?: boolean;
        tagline?: boolean;
        compact?: boolean;
    }>(),
    { dark: false, tagline: false, compact: false },
);

const source = computed(() => {
    if (props.compact) {
        return `/brand/icons/tracepilot-mark-${props.dark ? 'on-dark' : 'primary'}.svg`;
    }

    return `/brand/logos/svg/tracepilot-horizontal${props.tagline ? '-tagline' : ''}-${props.dark ? 'on-dark' : 'primary'}.svg`;
});
</script>

<template>
    <img
        :src="source"
        alt="TracePilot"
        class="block h-auto max-w-full"
        :class="compact ? 'w-9' : tagline ? 'w-80' : 'w-45'"
    />
</template>
