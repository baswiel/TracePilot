<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Licht' },
    { value: 'dark', Icon: Moon, label: 'Donker' },
    { value: 'system', Icon: Monitor, label: 'Systeem' },
] as const;
</script>

<template>
    <div class="border-border bg-muted inline-flex gap-1 rounded-xl border p-1">
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            :aria-pressed="appearance === value"
            @click="updateAppearance(value)"
            :class="[
                'focus-visible:outline-ring flex min-h-10 items-center rounded-lg px-3.5 py-2 transition-colors focus-visible:outline-2',
                appearance === value
                    ? 'text-accent-foreground bg-card shadow-xs'
                    : 'text-muted-foreground hover:bg-card hover:text-foreground',
            ]"
        >
            <component :is="Icon" class="-ml-1 h-4 w-4" />
            <span class="ml-1.5 text-sm">{{ label }}</span>
        </button>
    </div>
</template>
