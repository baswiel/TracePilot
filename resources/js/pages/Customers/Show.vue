<script setup lang="ts">
import WorkspaceHeader from '@/components/WorkspaceHeader.vue';
import WorkspacePage from '@/components/WorkspacePage.vue';
import PagePagination from '@/components/PagePagination.vue';
import type { Paginated } from '@/types/pagination';
import { Head, Link } from '@inertiajs/vue3';
import { FolderKanban, Pencil, Plus } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { create, show as showProject } from '@/routes/projects';
import { edit, index } from '@/routes/customers';

type Customer = { id: number; name: string; created_at: string };
type Project = {
    id: number;
    name: string;
    is_active: boolean;
    active_issues_count: number;
};

defineProps<{ customer: Customer; projects: Paginated<Project> }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Klanten', href: index() },
        ],
    },
});

const formatDate = (value: string) =>
    new Intl.DateTimeFormat('nl-NL', { dateStyle: 'medium' }).format(
        new Date(value),
    );
</script>

<template>
    <Head :title="customer.name" />

    <WorkspacePage>
        <WorkspaceHeader
            :title="customer.name"
            :description="`Klant sinds ${formatDate(customer.created_at)}`"
        >
            <Button variant="outline" as-child>
                <Link :href="edit(customer.id)"><Pencil /> Bewerken</Link>
            </Button>
            <Button as-child>
                <Link :href="create()"><Plus /> Project toevoegen</Link>
            </Button>
        </WorkspaceHeader>

        <Card class="overflow-hidden">
            <CardHeader class="border-b">
                <CardTitle as="h2">Projecten</CardTitle>
                <CardDescription>
                    Projecten die aan deze klant gekoppeld zijn.
                </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
                <div v-if="projects.data.length" class="divide-y">
                    <Link
                        v-for="project in projects.data"
                        :key="project.id"
                        :href="showProject(project.id)"
                        class="focus-visible:ring-ring hover:bg-muted/50 flex items-center justify-between gap-4 p-4 transition-colors outline-none focus-visible:ring-2 focus-visible:ring-inset sm:p-5"
                    >
                        <div>
                            <p class="font-medium">{{ project.name }}</p>
                            <p class="text-muted-foreground mt-1 text-sm">
                                {{ project.active_issues_count }}
                                {{
                                    project.active_issues_count === 1
                                        ? 'openstaande storing'
                                        : 'openstaande storingen'
                                }}
                            </p>
                        </div>
                        <Badge
                            :variant="
                                project.is_active ? 'outline' : 'secondary'
                            "
                        >
                            {{ project.is_active ? 'Actief' : 'Inactief' }}
                        </Badge>
                    </Link>
                </div>
                <div
                    v-else
                    class="flex flex-col items-center gap-3 px-6 py-14 text-center"
                >
                    <div class="bg-muted rounded-full p-3">
                        <FolderKanban class="text-muted-foreground size-6" />
                    </div>
                    <div>
                        <p class="font-medium">Nog geen projecten</p>
                        <p class="text-muted-foreground mt-1 text-sm">
                            Voeg een project toe en koppel het aan deze klant.
                        </p>
                    </div>
                    <Button as-child>
                        <Link :href="create()">Project toevoegen</Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
        <PagePagination :page="projects" />
    </WorkspacePage>
</template>
