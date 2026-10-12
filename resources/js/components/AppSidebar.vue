<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    Folder,
    House,
    Trash2,
} from '@lucide/vue';
import { computed } from 'vue';
import FolderController from '@/actions/App/Http/Controllers/FolderController';
import TrashController from '@/actions/App/Http/Controllers/TrashController';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { calendar, dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const navigation = computed(() => page.props.navigation);
const { isCurrentUrl } = useCurrentUrl();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: 'Trang chủ', href: dashboard(), icon: House },
    ];

    if (navigation.value?.truongVinhId) {
        items.push({
            title: 'Trường Vinh',
            href: FolderController.show(navigation.value.truongVinhId),
            icon: Building2,
        });
    }

    if (navigation.value?.companyRootId) {
        items.push({
            title: 'Công ty',
            href: FolderController.show(navigation.value.companyRootId),
            icon: BriefcaseBusiness,
        });
    }

    items.push({
        title: 'Lịch công việc',
        href: calendar(),
        icon: CalendarDays,
    });

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader class="px-3 pt-4">
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

        <SidebarContent class="gap-2">
            <NavMain :items="mainNavItems" />

            <SidebarGroup
                v-if="navigation?.pins.length"
                class="px-2 py-0 group-data-[collapsible=icon]:hidden"
            >
                <SidebarGroupLabel>Đã ghim</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem
                        v-for="pin in navigation.pins"
                        :key="pin.id"
                    >
                        <SidebarMenuButton
                            as-child
                            size="lg"
                            :is-active="
                                isCurrentUrl(FolderController.show(pin.id))
                            "
                        >
                            <Link :href="FolderController.show(pin.id)">
                                <Folder class="fill-amber-200 text-amber-600" />
                                <span class="grid leading-tight">
                                    <span class="truncate">{{ pin.name }}</span>
                                    <span
                                        v-if="pin.parent"
                                        class="truncate text-[13px] font-normal text-muted-foreground"
                                    >
                                        {{ pin.parent }}
                                    </span>
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        as-child
                        tooltip="Thùng rác"
                        :is-active="isCurrentUrl(TrashController.index())"
                    >
                        <Link :href="TrashController.index()">
                            <Trash2 />
                            <span>Thùng rác</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
