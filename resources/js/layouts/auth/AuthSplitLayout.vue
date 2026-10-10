<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import TracePilotLogo from '@/components/TracePilotLogo.vue';
import { home } from '@/routes';
defineProps<{ title?: string; description?: string }>();
</script>
<template>
    <div
        class="bg-background grid min-h-svh lg:grid-cols-[minmax(22rem,0.85fr)_minmax(0,1.15fr)]"
    >
        <aside
            class="hidden flex-col justify-between bg-[#101d3f] p-10 text-white lg:flex xl:p-14"
        >
            <Link :href="home()" class="block w-64" aria-label="TracePilot"
                ><TracePilotLogo dark tagline
            /></Link>
            <div class="my-16 max-w-md">
                <h2
                    class="text-4xl leading-tight font-semibold tracking-[-0.035em]"
                >
                    Overzicht wanneer het erop aankomt.
                </h2>
                <p class="mt-5 text-base leading-7 text-slate-300">
                    Bewaak afspraken, handel issues af en houd je team altijd op
                    de hoogte.
                </p>
                <ul
                    class="mt-8 space-y-4 border-t border-white/15 pt-8 text-sm text-slate-200"
                >
                    <li class="flex items-center gap-3">
                        <Check class="size-4 shrink-0 text-blue-300" />Realtime
                        overzicht van actieve issues
                    </li>
                    <li class="flex items-center gap-3">
                        <Check class="size-4 shrink-0 text-blue-300" />Heldere
                        opvolging met checklists
                    </li>
                </ul>
            </div>
            <p class="text-sm text-slate-300">
                Houd de impact van storingen beheersbaar.
            </p>
        </aside>
        <main
            class="flex items-center justify-center px-5 py-8 sm:p-10 lg:p-14"
        >
            <div class="w-full max-w-md">
                <Link
                    :href="home()"
                    class="mb-8 block w-48 lg:hidden"
                    aria-label="TracePilot"
                    ><TracePilotLogo class="dark:hidden" /><TracePilotLogo
                        dark
                        class="hidden dark:block"
                /></Link>
                <div class="bg-card rounded-xl border p-5 sm:p-8">
                    <header class="mb-7">
                        <h1
                            v-if="title"
                            class="text-foreground text-2xl font-semibold tracking-tight"
                        >
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="text-muted-foreground mt-2 text-sm leading-6"
                        >
                            {{ description }}
                        </p>
                    </header>
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>
