<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ClipboardList,
    Building2,
    FolderKanban,
    LayoutGrid,
    Settings,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as issues } from '@/routes/issues';
import { index as projects } from '@/routes/projects';
import { index as team } from '@/routes/team';
import { index as customers } from '@/routes/customers';
import { edit as editProfile } from '@/routes/profile';
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Projecten',
        href: projects(),
        icon: FolderKanban,
    },
    {
        title: 'Klanten',
        href: customers(),
        icon: Building2,
    },
    {
        title: 'Alle storingen',
        href: issues(),
        icon: ClipboardList,
        badge: page.props.activeIssuesCount,
    },
    {
        title: 'Team',
        href: team(),
        icon: Users,
    },
    {
        title: 'Instellingen',
        href: editProfile(),
        icon: Settings,
    },
]);

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar" class="border-r bg-white">
        <SidebarHeader class="px-4 pt-5 pb-6">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="px-2">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter class="border-t px-3 py-4">
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
