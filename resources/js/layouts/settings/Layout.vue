<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import {
    UserRound,
    ShieldCheck,
    Palette,
    ListChecks,
    Timer,
    CalendarClock,
    ChevronDown,
} from '@lucide/vue';
import WorkspacePage from '@/components/WorkspacePage.vue';
import WorkspaceHeader from '@/components/WorkspaceHeader.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as editIssueChecklist } from '@/routes/issue-checklist';
import { index as editSlaLevels } from '@/routes/sla-levels';
import { edit as editBusinessHours } from '@/routes/business-hours';
const groups = [
    {
        title: 'Account',
        items: [
            { title: 'Profiel', href: editProfile(), icon: UserRound },
            { title: 'Beveiliging', href: editSecurity(), icon: ShieldCheck },
            { title: 'Weergave', href: editAppearance(), icon: Palette },
        ],
    },
    {
        title: 'Incidentbeheer',
        items: [
            {
                title: 'Issue-checklist',
                href: editIssueChecklist(),
                icon: ListChecks,
            },
            { title: 'SLA-niveaus', href: editSlaLevels(), icon: Timer },
            {
                title: 'Werkuren',
                href: editBusinessHours(),
                icon: CalendarClock,
            },
        ],
    },
];
const { isCurrentOrParentUrl } = useCurrentUrl();
const navigationOpen = ref(false);
const currentItem = computed(
    () =>
        groups
            .flatMap((group) => group.items)
            .find((item) => isCurrentOrParentUrl(item.href)) ??
        groups[0].items[0],
);
</script>
<template>
    <WorkspacePage>
        <WorkspaceHeader
            title="Instellingen"
            description="Beheer je account en de afspraken voor incidentafhandeling."
        />
        <div
            class="grid items-start gap-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8"
        >
            <aside class="min-w-0 lg:sticky lg:top-24">
                <Button
                    variant="outline"
                    class="w-full justify-between lg:hidden"
                    :aria-expanded="navigationOpen"
                    aria-controls="settings-navigation"
                    @click="navigationOpen = !navigationOpen"
                    ><span class="flex items-center gap-2"
                        ><component :is="currentItem.icon" class="size-4" />{{
                            currentItem.title
                        }}</span
                    ><span
                        class="text-muted-foreground flex items-center gap-2 text-xs"
                        >Andere instelling<ChevronDown class="size-4" /></span
                ></Button>
                <nav
                    id="settings-navigation"
                    aria-label="Instellingen"
                    :class="[
                        'space-y-4 lg:block lg:space-y-6',
                        navigationOpen ? 'mt-4' : 'hidden',
                    ]"
                >
                    <div v-for="group in groups" :key="group.title">
                        <p
                            class="text-muted-foreground mb-2 px-3 text-xs font-medium"
                        >
                            {{ group.title }}
                        </p>
                        <div
                            class="grid grid-cols-2 gap-1 sm:grid-cols-3 lg:grid-cols-1"
                        >
                            <Link
                                v-for="item in group.items"
                                :key="toUrl(item.href)"
                                :href="item.href"
                                @click="navigationOpen = false"
                                :aria-current="
                                    isCurrentOrParentUrl(item.href)
                                        ? 'page'
                                        : undefined
                                "
                                :class="[
                                    'focus-visible:outline-ring flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-2 motion-reduce:transition-none',
                                    isCurrentOrParentUrl(item.href)
                                        ? 'bg-accent text-primary'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                ]"
                            >
                                <component
                                    :is="item.icon"
                                    class="size-4 shrink-0"
                                />{{ item.title }}
                            </Link>
                        </div>
                    </div>
                </nav>
            </aside>
            <div class="settings-content min-w-0 space-y-6"><slot /></div>
        </div>
    </WorkspacePage>
</template>
