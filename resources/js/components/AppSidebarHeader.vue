<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import IssueReportDialog from '@/components/issues/IssueReportDialog.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { index, show } from '@/routes/issues';
import type { BreadcrumbItem } from '@/types';

withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItem[] }>(), {
    breadcrumbs: () => [],
});

type Notification = {
    id: number;
    title: string;
    project: string;
    status: 'open' | 'handling';
};
type ReportIssueOptions = {
    projects: Array<{ id: number; name: string; customer_name: string | null }>;
    teamMembers: Array<{ id: number; name: string; email: string }>;
};

const page = usePage();
const isReportDialogOpen = ref(false);
const reportIssueOptions = computed(
    () => page.props.reportIssueOptions as ReportIssueOptions,
);
const notifications = computed(
    () => page.props.notifications as Notification[],
);
const activeIssuesCount = computed(() =>
    Number(page.props.activeIssuesCount ?? 0),
);
</script>

<template>
    <header
        class="flex h-24 shrink-0 items-center justify-between gap-3 px-5 transition-[width,height] ease-linear md:px-8"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-2">
            <Button
                class="h-11 rounded-lg px-4 shadow-lg shadow-blue-500/15"
                @click="isReportDialogOpen = true"
            >
                <Plus />
                <span class="hidden sm:inline">Storing melden</span>
            </Button>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        class="text-muted-foreground relative"
                        size="icon"
                        variant="ghost"
                        aria-label="Meldingen"
                    >
                        <Bell />
                        <span
                            v-if="activeIssuesCount"
                            class="ring-background absolute top-2 right-2 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-semibold text-white ring-2"
                            >{{
                                activeIssuesCount > 9 ? '9+' : activeIssuesCount
                            }}</span
                        >
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-80 p-2">
                    <DropdownMenuLabel
                        class="flex items-center justify-between px-2 py-2"
                    >
                        <span>Meldingen</span>
                        <span class="text-muted-foreground text-xs font-normal"
                            >{{ activeIssuesCount }} actief</span
                        >
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <div v-if="notifications.length" class="py-1">
                        <DropdownMenuItem
                            v-for="notification in notifications"
                            :key="notification.id"
                            as-child
                            class="h-auto cursor-pointer items-start rounded-md px-2 py-2.5"
                        >
                            <Link
                                :href="show(notification.id)"
                                class="block w-full"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="size-2 rounded-full"
                                        :class="
                                            notification.status === 'open'
                                                ? 'bg-red-500'
                                                : 'bg-amber-500'
                                        "
                                    />
                                    <span
                                        class="truncate font-medium text-[#101d3f]"
                                        >{{ notification.title }}</span
                                    >
                                </div>
                                <span
                                    class="text-muted-foreground mt-1 block pl-4 text-xs"
                                    >{{ notification.project }} ·
                                    {{
                                        notification.status === 'open'
                                            ? 'Open'
                                            : 'In behandeling'
                                    }}</span
                                >
                            </Link>
                        </DropdownMenuItem>
                    </div>
                    <p
                        v-else
                        class="text-muted-foreground px-2 py-5 text-center text-sm"
                    >
                        Geen actieve storingen.
                    </p>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        as-child
                        class="text-primary cursor-pointer justify-center py-2"
                    >
                        <Link :href="index()">Alle storingen bekijken</Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
    <IssueReportDialog
        v-model:open="isReportDialogOpen"
        :projects="reportIssueOptions.projects"
        :team-members="reportIssueOptions.teamMembers"
    />
</template>
