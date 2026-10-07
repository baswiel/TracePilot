<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check, ShieldCheck, TriangleAlert } from '@lucide/vue';
import TracePilotLogo from '@/components/TracePilotLogo.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="bg-background grid min-h-svh lg:grid-cols-[minmax(420px,1fr)_minmax(520px,1.15fr)]"
    >
        <div
            class="relative hidden overflow-hidden bg-[#101d3f] p-12 text-white lg:flex lg:flex-col"
        >
            <div
                class="absolute -right-32 -bottom-40 size-125 rounded-full border-[32px] border-blue-400/15"
            />
            <div
                class="absolute -top-28 -left-20 size-72 rounded-full bg-blue-500/15 blur-3xl"
            />
            <Link :href="home()" class="relative z-20 block w-80">
                <TracePilotLogo dark tagline />
            </Link>
            <div class="relative z-10 my-auto max-w-md">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-blue-300/25 bg-white/8 px-3 py-1.5 text-sm text-blue-100"
                >
                    <ShieldCheck class="size-4" /> TracePilot
                </span>
                <h2
                    class="mt-7 text-4xl leading-tight font-semibold tracking-[-0.04em]"
                >
                    Overzicht wanneer het erop aankomt.
                </h2>
                <p class="mt-5 text-lg leading-8 text-slate-300">
                    Bewaak afspraken, handel issues af en houd je team altijd op
                    de hoogte.
                </p>
                <ul class="mt-10 space-y-4 text-slate-200">
                    <li class="flex items-center gap-3">
                        <span
                            class="grid size-6 place-items-center rounded-full bg-emerald-400/15 text-emerald-300"
                            ><Check class="size-4" /></span
                        >Realtime overzicht van actieve issues
                    </li>
                    <li class="flex items-center gap-3">
                        <span
                            class="grid size-6 place-items-center rounded-full bg-emerald-400/15 text-emerald-300"
                            ><Check class="size-4" /></span
                        >Heldere opvolging met checklists
                    </li>
                </ul>
            </div>
            <div
                class="relative z-10 flex items-center gap-3 rounded-xl border border-white/10 bg-white/6 p-4 text-sm text-slate-200"
            >
                <TriangleAlert class="size-5 shrink-0 text-orange-300" />
                Houd de impact van storingen beheersbaar.
            </div>
        </div>
        <div class="flex items-center justify-center p-6 sm:p-10 lg:p-14">
            <div
                class="border-border bg-card w-full max-w-[420px] rounded-2xl border p-7 shadow-[0_12px_32px_rgb(30_51_83_/_6%)] sm:p-10"
            >
                <Link :href="home()" class="mb-10 block w-45 lg:hidden">
                    <TracePilotLogo />
                </Link>
                <div class="mb-8 flex flex-col space-y-2">
                    <h1
                        class="text-foreground text-3xl font-semibold tracking-[-0.035em]"
                        v-if="title"
                    >
                        {{ title }}
                    </h1>
                    <p
                        class="text-muted-foreground leading-6"
                        v-if="description"
                    >
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
