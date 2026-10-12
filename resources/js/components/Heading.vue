<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header :class="variant === 'small' ? '' : 'space-y-1'">
        <h2
            class="text-balance"
            :class="
                variant === 'small'
                    ? 'mb-1 text-lg font-semibold tracking-tight'
                    : 'text-xl font-semibold tracking-tight'
            "
        >
            {{ title }}
        </h2>
        <p v-if="description" class="text-muted-foreground text-sm text-pretty">
            {{ description }}
        </p>
    </header>
</template>
