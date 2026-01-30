<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
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
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, List, MegaphoneOff, Send, Tag, Users, FolderClock, Megaphone } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Terms Negative',
        href: '/terms',
        icon: List,
    },
    {
        title: 'Frasa Negative',
        href: '/frasa',
        icon: List,
    },
    {
        title: 'Blacklist Words',
        href: '/blacklist-words',
        icon: MegaphoneOff,
    },
    {
        title: 'Kirim Konversi',
        href: '/kirim-konversi',
        icon: Send,
    },
    {
        title: 'Search Terms None',
        href: '/search-terms-none',
        icon: Tag,
    },
    {
        title: 'Iklan Responsif',
        href: '/iklan-responsif',
        icon: Megaphone,
    },
];

const mainNavItems2: NavItem[] = [
    {
        title: 'Users',
        href: '/users',
        icon: Users,
    },
    {
        title: 'Cron Logs',
        href: '/cron-logs',
        icon: FolderClock,
    },
];

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
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

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Adsense" />
            <NavMain :items="mainNavItems2" label="Platform" class="mt-4" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <ThemeToggle />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
