<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as editIssueChecklist } from '@/routes/issue-checklist';
import { index as editSlaLevels } from '@/routes/sla-levels';
import { edit as editBusinessHours } from '@/routes/business-hours';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profiel',
        href: editProfile(),
    },
    {
        title: 'Beveiliging',
        href: editSecurity(),
    },
    {
        title: 'Weergave',
        href: editAppearance(),
    },
    {
        title: 'Issue-checklist',
        href: editIssueChecklist(),
    },
    {
        title: 'SLA-niveaus',
        href: editSlaLevels(),
    },
    {
        title: 'Werkuren',
        href: editBusinessHours(),
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="mx-auto w-full max-w-[1440px] px-5 py-3 sm:px-8">
        <Heading
            title="Instellingen"
            description="Beheer je profiel en accountinstellingen"
        />

        <div class="flex flex-col gap-6 lg:flex-row lg:gap-10">
            <aside class="w-full max-w-xl lg:w-56">
                <nav
                    class="bg-card flex flex-col space-y-1 rounded-xl border p-2 shadow-sm"
                    aria-label="Instellingen"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            {
                                'text-primary bg-blue-50': isCurrentOrParentUrl(
                                    item.href,
                                ),
                            },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section
                    class="bg-card max-w-xl space-y-10 rounded-xl border p-6 shadow-sm"
                >
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
