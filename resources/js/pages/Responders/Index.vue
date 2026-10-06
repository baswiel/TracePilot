<script setup lang="ts">
import PagePagination from '@/components/PagePagination.vue';
import type { Paginated } from '@/types/pagination';
import { Head, Link } from '@inertiajs/vue3';
import { CircleUserRound, FolderKanban } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { show as showProject } from '@/routes/projects';
import { index } from '@/routes/responders';

type Project = {
    id: number;
    name: string;
    customer_name: string | null;
    is_active: boolean;
    roles: string[];
};

type Responder = {
    id: number;
    name: string;
    email: string | null;
    projects: Project[];
};

defineProps<{ responders: Paginated<Responder> }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Responders', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Responders" />

    <div
        class="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-6 px-5 pt-2 pb-10 sm:px-8"
    >
        <section>
            <h1 class="text-2xl font-semibold tracking-tight text-[#101d3f]">
                Responders
            </h1>
            <p class="text-muted-foreground mt-1 text-sm">
                Bekijk per responder voor welke projecten die is ingepland.
            </p>
        </section>

        <div v-if="responders.data.length" class="grid gap-5 lg:grid-cols-2">
            <Card v-for="responder in responders.data" :key="responder.id">
                <CardHeader class="border-b">
                    <div class="flex items-start gap-3">
                        <div
                            class="bg-muted flex size-10 shrink-0 items-center justify-center rounded-full"
                        >
                            <CircleUserRound
                                class="text-muted-foreground size-5"
                            />
                        </div>
                        <div class="min-w-0">
                            <CardTitle>{{ responder.name }}</CardTitle>
                            <CardDescription
                                v-if="responder.email"
                                class="mt-1"
                            >
                                {{ responder.email }}
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div v-if="responder.projects.length" class="divide-y">
                        <Link
                            v-for="project in responder.projects"
                            :key="project.id"
                            :href="showProject(project.id)"
                            class="hover:bg-muted/50 flex items-start justify-between gap-4 p-4 transition-colors"
                        >
                            <div class="min-w-0">
                                <p class="font-medium">{{ project.name }}</p>
                                <p
                                    v-if="project.customer_name"
                                    class="text-muted-foreground mt-1 text-sm"
                                >
                                    {{ project.customer_name }}
                                </p>
                            </div>
                            <div
                                class="flex shrink-0 flex-col items-end gap-1.5"
                            >
                                <Badge variant="outline">
                                    {{ project.roles.join(', ') }}
                                </Badge>
                                <span
                                    v-if="!project.is_active"
                                    class="text-muted-foreground text-xs"
                                >
                                    Gearchiveerd
                                </span>
                            </div>
                        </Link>
                    </div>
                    <div v-else class="px-5 py-8 text-center">
                        <p class="font-medium">Nog geen projecten</p>
                        <p class="text-muted-foreground mt-1 text-sm">
                            Deze responder is nog niet aan een project
                            gekoppeld.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card v-else>
            <CardContent
                class="flex flex-col items-center gap-3 px-6 py-14 text-center"
            >
                <FolderKanban class="text-muted-foreground size-8" />
                <p class="mt-3 font-medium">Nog geen responders</p>
                <p class="text-muted-foreground mt-1 text-sm">
                    Voeg eerst teamleden toe om responders aan projecten te
                    koppelen.
                </p>
            </CardContent>
        </Card>
        <PagePagination :page="responders" />
    </div>
</template>
