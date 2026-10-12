<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Megaphone, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import NoticeController from '@/actions/App/Http/Controllers/NoticeController';
import NoticeDialog from '@/components/app/NoticeDialog.vue';
import { Button } from '@/components/ui/button';

type Notice = {
    id: number;
    title: string;
    body: string | null;
    author: string;
    date: string | null;
    canDelete: boolean;
};

defineProps<{
    notices: {
        data: Notice[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Thông báo chung', href: NoticeController.index() },
        ],
    },
});

const dialogOpen = ref(false);

function remove(notice: Notice) {
    router.delete(NoticeController.destroy.url(notice.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Thông báo chung" />

    <div
        class="mx-auto flex w-full max-w-[880px] flex-col gap-6 px-4 py-6 md:px-10 md:py-8"
    >
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h1
                class="flex items-center gap-3 text-[32px] leading-tight font-medium tracking-tight"
            >
                <Megaphone class="size-7 text-[#2563c9]" />
                Thông báo chung
            </h1>
            <Button size="lg" class="h-11" @click="dialogOpen = true">
                <Plus />
                Đăng tin
            </Button>
        </div>

        <div v-if="notices.data.length" class="grid gap-3">
            <article
                v-for="notice in notices.data"
                :key="notice.id"
                class="grid gap-1.5 rounded-2xl border bg-card px-5 py-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-[17px] font-medium">{{ notice.title }}</h2>
                    <Button
                        v-if="notice.canDelete"
                        variant="ghost"
                        size="icon"
                        :aria-label="`Xoá thông báo ${notice.title}`"
                        @click="remove(notice)"
                    >
                        <Trash2 />
                    </Button>
                </div>
                <p
                    v-if="notice.body"
                    class="leading-relaxed font-light whitespace-pre-line text-[#3b4954]"
                >
                    {{ notice.body }}
                </p>
                <p class="text-[13px] text-muted-foreground">
                    {{ notice.author }}, {{ notice.date }}
                </p>
            </article>
        </div>
        <p
            v-else
            class="rounded-2xl border border-dashed bg-card px-6 py-10 text-center text-muted-foreground"
        >
            Chưa có thông báo nào.
        </p>
    </div>

    <NoticeDialog v-model:open="dialogOpen" />
</template>
