<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { search } from '@/routes';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const query = ref('');

function submit() {
    if (query.value.trim().length >= 2) {
        router.get(search.url({ query: { q: query.value.trim() } }));
    }
}
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-3 border-b border-sidebar-border/70 bg-card/60 px-4 transition-[width,height] ease-linear md:px-6"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <form
            role="search"
            class="hidden h-10 w-full max-w-xs items-center gap-2 rounded-lg border bg-card px-3 focus-within:border-primary md:flex"
            @submit.prevent="submit"
        >
            <Search class="size-4 text-muted-foreground" />
            <input
                v-model="query"
                type="search"
                aria-label="Tìm nhanh"
                placeholder="Tìm nhanh"
                class="min-w-0 flex-1 bg-transparent text-sm outline-none"
            />
        </form>
    </header>
</template>
