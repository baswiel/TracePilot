<script setup lang="ts">
defineProps<{ title: string; description?: string }>();
</script>
<template>
    <header
        class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
    >
        <div class="min-w-0">
            <h1
                class="text-foreground text-2xl font-semibold tracking-tight break-words"
            >
                {{ title }}
            </h1>
            <p
                v-if="description"
                class="text-muted-foreground mt-1 max-w-prose text-sm leading-6"
            >
                {{ description }}
            </p>
            <div
                v-if="$slots.context"
                class="mt-3 flex flex-wrap items-center gap-2"
            >
                <slot name="context" />
            </div>
        </div>
        <div
            v-if="$slots.default"
            class="flex shrink-0 flex-wrap items-center gap-2"
        >
            <slot />
        </div>
    </header>
</template>
