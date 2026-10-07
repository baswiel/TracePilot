<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10"
    >
        <div class="flex w-full max-w-md flex-col gap-6">
            <Link
                :href="home()"
                class="text-foreground flex items-center gap-3 self-center font-semibold"
            >
                <div
                    class="bg-primary flex h-10 w-10 items-center justify-center rounded-xl p-2"
                >
                    <AppLogoIcon class="size-7 fill-current text-white" />
                </div>
            </Link>

            <div class="flex flex-col gap-6">
                <Card class="border-border rounded-2xl py-0">
                    <CardHeader class="px-10 pt-9 pb-0 text-center">
                        <CardTitle
                            class="text-foreground text-2xl tracking-tight"
                            >{{ title }}</CardTitle
                        >
                        <CardDescription>
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-10 py-8">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
