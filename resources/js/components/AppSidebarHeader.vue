<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, LoaderCircle, Plus } from '@lucide/vue';
import { computed, ref, shallowRef } from 'vue';
import { toast } from 'vue-sonner';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import type IssueReportDialog from '@/components/issues/IssueReportDialog.vue';
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
};

const page = usePage();
const headerElement = ref<HTMLElement>();

const restoreReportFocus = () => {
    headerElement.value
        ?.querySelector<HTMLButtonElement>(
            'button[aria-label="Storing melden"]',
        )
        ?.focus();
};
const isReportDialogOpen = ref(false);
const isReportDialogLoading = ref(false);
const reportDialog = shallowRef<typeof IssueReportDialog>();

async function openReportDialog() {
    if (isReportDialogLoading.value) return;

    if (!reportDialog.value) {
        isReportDialogLoading.value = true;
        try {
            reportDialog.value = (
                await import('@/components/issues/IssueReportDialog.vue')
            ).default;
        } catch {
            toast.error(
                'Het storingformulier kon niet laden. Probeer opnieuw.',
            );
            return;
        } finally {
            isReportDialogLoading.value = false;
        }
    }

    isReportDialogOpen.value = true;
}
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
        ref="headerElement"
        class="bg-card sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-3 border-b px-5 md:px-8"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1 size-10" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs class="min-w-0" :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <Button
                variant="outline"
                class="h-10 rounded-lg px-3"
                aria-label="Storing melden"
                :aria-busy="isReportDialogLoading"
                :disabled="isReportDialogLoading"
                @click="openReportDialog"
            >
                <LoaderCircle
                    v-if="isReportDialogLoading"
                    class="animate-spin motion-reduce:animate-none"
                    aria-hidden="true"
                />
                <Plus v-else />
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
                <DropdownMenuContent
                    align="end"
                    class="w-80 max-w-[calc(100vw-2rem)] p-2"
                >
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
                                <div class="flex min-w-0 items-center gap-2">
                                    <span
                                        class="size-2 shrink-0 rounded-full"
                                        :class="
                                            notification.status === 'open'
                                                ? 'bg-red-500'
                                                : 'bg-amber-500'
                                        "
                                    />
                                    <span
                                        class="text-foreground min-w-0 truncate font-medium"
                                        :title="notification.title"
                                        >{{ notification.title }}</span
                                    >
                                </div>
                                <span
                                    class="text-muted-foreground mt-1 block pl-4 text-xs wrap-anywhere"
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
    <component
        :is="reportDialog"
        v-if="reportDialog"
        v-model:open="isReportDialogOpen"
        :projects="reportIssueOptions.projects"
        @closed="restoreReportFocus"
    />
</template>
