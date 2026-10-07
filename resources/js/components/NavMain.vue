<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
    label?: string;
}>();

const { isCurrentOrParentUrl } = useCurrentUrl();
const { setOpenMobile } = useSidebar();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel
            v-if="label"
            class="text-muted-foreground mb-2 text-xs font-medium"
            >{{ label }}</SidebarGroupLabel
        >
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <SidebarMenuItem>
                    <SidebarMenuButton
                        as-child
                        :is-active="isCurrentOrParentUrl(item.href)"
                        :tooltip="item.title"
                        class="h-11"
                    >
                        <Link
                            :href="item.href"
                            @click="setOpenMobile(false)"
                            :aria-current="
                                isCurrentOrParentUrl(item.href)
                                    ? 'page'
                                    : undefined
                            "
                        >
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                            <span
                                v-if="item.badge"
                                class="bg-destructive/10 text-destructive ml-auto inline-flex min-w-5 items-center justify-center rounded-md px-1.5 py-0.5 text-xs font-semibold tabular-nums"
                                aria-label="Actieve storingen"
                            >
                                {{ item.badge > 99 ? '99+' : item.badge }}
                            </span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
