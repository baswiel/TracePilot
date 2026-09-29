<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FolderKanban, Plus, Search } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { create, index, show } from '@/routes/projects';

type Project = {
    id: number;
    name: string;
    customer_name: string | null;
    is_active: boolean;
    active_issues_count: number;
    latest_issue_at: string | null;
};

type ProjectsPagination = {
    data: Project[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    projects: ProjectsPagination;
    filters: { search: string; status: string };
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Projecten', href: index() },
        ],
    },
});

const applyFilters = () => {
    router.get(
        index().url,
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('nl-NL', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : 'Nog geen storingen';
</script>

<template>
    <Head title="Projecten" />

    <div
        class="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-6 px-5 pt-2 pb-10 sm:px-8"
    >
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-semibold tracking-tight text-[#101d3f]"
                >
                    Projecten
                </h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Beheer projecten en houd de lopende storingen per klant bij.
                </p>
            </div>
            <Button as-child>
                <Link :href="create()"><Plus /> Project toevoegen</Link>
            </Button>
        </div>

        <Card>
            <CardContent class="p-4 sm:p-5">
                <form
                    class="grid gap-3 md:grid-cols-[1fr_12rem_auto]"
                    @submit.prevent="applyFilters"
                >
                    <div class="relative">
                        <Search
                            class="text-muted-foreground absolute top-2.5 left-3 size-4"
                        />
                        <Input
                            v-model="search"
                            class="pl-9"
                            placeholder="Zoek op project- of klantnaam"
                        />
                    </div>
                    <select
                        v-model="status"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    >
                        <option value="">Alle statussen</option>
                        <option value="active">Actief</option>
                        <option value="inactive">Inactief</option>
                    </select>
                    <Button type="submit" variant="outline">Filteren</Button>
                </form>
            </CardContent>
        </Card>

        <Card v-if="projects.data.length" class="overflow-hidden">
            <div class="divide-y">
                <Link
                    v-for="project in projects.data"
                    :key="project.id"
                    :href="show(project.id)"
                    class="focus-visible:ring-ring hover:bg-muted/50 flex flex-col gap-4 p-4 transition-colors outline-none focus-visible:ring-2 focus-visible:ring-inset sm:p-5 lg:grid lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_10rem_12rem] lg:items-center"
                >
                    <div>
                        <p class="font-medium">{{ project.name }}</p>
                        <p class="text-muted-foreground mt-1 text-sm">
                            {{ project.customer_name || 'Geen klantnaam' }}
                        </p>
                    </div>
                    <div class="text-sm">
                        <p class="text-muted-foreground">
                            Openstaande storingen
                        </p>
                        <p class="mt-1 font-medium">
                            {{ project.active_issues_count }}
                        </p>
                    </div>
                    <div>
                        <Badge
                            :variant="
                                project.is_active ? 'default' : 'secondary'
                            "
                        >
                            {{ project.is_active ? 'Actief' : 'Inactief' }}
                        </Badge>
                    </div>
                    <div class="text-sm">
                        <p class="text-muted-foreground">
                            Meest recente storing
                        </p>
                        <p class="mt-1">
                            {{ formatDate(project.latest_issue_at) }}
                        </p>
                    </div>
                </Link>
            </div>
        </Card>

        <Card v-else>
            <CardContent
                class="flex flex-col items-center gap-3 px-6 py-14 text-center"
            >
                <div class="bg-muted rounded-full p-3">
                    <FolderKanban class="text-muted-foreground size-6" />
                </div>
                <div>
                    <h2 class="font-medium">Geen projecten gevonden</h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Pas je filters aan of voeg het eerste project toe.
                    </p>
                </div>
                <Button as-child
                    ><Link :href="create()">Project toevoegen</Link></Button
                >
            </CardContent>
        </Card>

        <div
            v-if="projects.last_page > 1"
            class="flex items-center justify-between"
        >
            <p class="text-muted-foreground text-sm">
                {{ projects.total }} projecten
            </p>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!projects.prev_page_url"
                    as-child
                >
                    <Link
                        v-if="projects.prev_page_url"
                        :href="projects.prev_page_url"
                        >Vorige</Link
                    >
                    <span v-else>Vorige</span>
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!projects.next_page_url"
                    as-child
                >
                    <Link
                        v-if="projects.next_page_url"
                        :href="projects.next_page_url"
                        >Volgende</Link
                    >
                    <span v-else>Volgende</span>
                </Button>
            </div>
        </div>
    </div>
</template>
