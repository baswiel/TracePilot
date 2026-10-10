<script setup lang="ts">
withDefaults(defineProps<{ compact?: boolean }>(), { compact: false });
</script>
<template>
    <div
        :class="[
            'mx-auto flex w-full flex-1 flex-col gap-6 px-5 pt-7 pb-10 sm:px-8',
            compact ? 'max-w-3xl' : 'max-w-[1440px]',
        ]"
    >
        <slot />
    </div>
</template>
