<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useResizeObserver } from '@vueuse/core';
import { onMounted, ref, useTemplateRef, watch } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();
const bar = useTemplateRef<HTMLDivElement>('bar');
const pill = useTemplateRef<HTMLSpanElement>('pill');
const pillReady = ref(false);

function movePill(animate: boolean) {
    const container = bar.value;
    const active = container?.querySelector<HTMLButtonElement>(
        '[aria-pressed="true"]',
    );
    const indicator = pill.value;

    if (!container || !active || !indicator) return;

    const previousTransition = indicator.style.transition;
    if (!animate) indicator.style.transition = 'none';

    const activeBounds = active.getBoundingClientRect();
    const barBounds = container.getBoundingClientRect();
    const left = activeBounds.left - barBounds.left - container.clientLeft;
    indicator.style.transform = `translateX(${left}px)`;
    indicator.style.width = `${activeBounds.width}px`;

    if (!animate) {
        // Place the first selection and resized controls without a fly-in.
        void indicator.offsetWidth;
        indicator.style.transition = previousTransition;
    }
    pillReady.value = true;
}

onMounted(() => movePill(false));
watch(appearance, () => movePill(true), { flush: 'post' });
useResizeObserver(bar, () => movePill(false));

const tabs = [
    { value: 'light', Icon: Sun, label: 'Licht' },
    { value: 'dark', Icon: Moon, label: 'Donker' },
    { value: 'system', Icon: Monitor, label: 'Systeem' },
] as const;
</script>

<template>
    <div class="appearance-control">
        <div
            ref="bar"
            class="t-tabs appearance-tabs border-border border"
            role="group"
            aria-label="Weergave"
        >
            <span ref="pill" class="t-tabs-pill shadow-xs" aria-hidden="true" />
            <button
                v-for="{ value, Icon, label } in tabs"
                :key="value"
                type="button"
                :aria-pressed="appearance === value"
                @click="updateAppearance(value)"
                class="t-tab focus-visible:outline-ring flex min-h-10 items-center focus-visible:outline-2"
                :class="{
                    'bg-card shadow-xs': appearance === value && !pillReady,
                    'hover:bg-card': appearance !== value,
                }"
            >
                <component
                    :is="Icon"
                    class="-ml-1 size-4 shrink-0"
                    aria-hidden="true"
                />
                <span class="ml-1.5 text-sm">{{ label }}</span>
            </button>
        </div>
    </div>
</template>
