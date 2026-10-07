<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ClipboardList,
    ChartNoAxesCombined,
    Building2,
    FolderKanban,
    LayoutGrid,
    ShieldCheck,
    Settings,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
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
import { index as reports } from '@/routes/reports';
import { index as issues } from '@/routes/issues';
import { index as projects } from '@/routes/projects';
import { index as responders } from '@/routes/responders';
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
        title: 'Rapportages',
        href: reports(),
        icon: ChartNoAxesCombined,
    },
    {
        title: 'Responders',
        href: responders(),
        icon: ShieldCheck,
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

const workNavItems = computed(() => [
    mainNavItems.value[0],
    mainNavItems.value[3],
    mainNavItems.value[4],
]);
const managementNavItems = computed(() => [
    mainNavItems.value[1],
    mainNavItems.value[2],
    mainNavItems.value[5],
    mainNavItems.value[6],
]);
const footerNavItems = computed(() => [mainNavItems.value[7]]);
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar" class="bg-sidebar border-r">
        <SidebarHeader class="px-4 pt-5 pb-5">
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

        <SidebarContent class="gap-6 px-2">
            <NavMain :items="workNavItems" label="Werkplek" />
            <div class="mx-4 border-t" />
            <NavMain :items="managementNavItems" label="Beheer" />
        </SidebarContent>

        <SidebarFooter class="border-t px-3 py-4">
            <NavMain :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
