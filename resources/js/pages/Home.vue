<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronRight, Megaphone, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import FolderController from '@/actions/App/Http/Controllers/FolderController';
import NoticeController from '@/actions/App/Http/Controllers/NoticeController';
import FileBadge from '@/components/app/FileBadge.vue';
import NoticeDialog from '@/components/app/NoticeDialog.vue';
import RecurringPanel from '@/components/app/RecurringPanel.vue';
import TodoPanel from '@/components/app/TodoPanel.vue';
import { givenName } from '@/lib/format';
import { search } from '@/routes';
import type { DocumentItem } from '@/types';

type Notice = {
    id: number;
    title: string;
    body: string | null;
    author: string;
    date: string | null;
};

type Recurring = {
    week: Array<{
        date: string;
        day: number;
        weekday: string;
        isToday: boolean;
        busy: boolean;
    }>;
    upcoming: Array<{
        id: number;
        title: string;
        rule: string;
        dueOn: string;
        day: string;
        month: string;
        isToday: boolean;
    }>;
};

defineProps<{
    today: string;
    notices: Notice[];
    truongVinh: {
        id: number;
        name: string;
        children: Array<{ id: number; name: string; count: number }>;
    };
    companies: {
        rootId: number;
        total: number;
        items: Array<{ id: number; name: string; projects: number }>;
    };
    recent: DocumentItem[];
    todos: Array<{
        id: number;
        title: string;
        dueOn: string | null;
        done: boolean;
    }>;
    recurring: Recurring;
}>();

defineOptions({
    layout: { breadcrumbs: [] },
});

const page = usePage();
const firstName = computed(() => givenName(page.props.auth.user.name));
const query = ref('');
const noticeOpen = ref(false);

const companyTones = [
    'bg-accent text-accent-foreground',
    'bg-[#e5f0ff] text-[#1f55a8]',
    'bg-[#fff1d6] text-[#965006]',
];

function submitSearch() {
    if (query.value.trim().length < 2) {
        return;
    }

    router.get(search.url({ query: { q: query.value.trim() } }));
}

function initial(name: string): string {
    return (
        name
            .replace(/^Công ty\s*/i, '')
            .charAt(0)
            .toUpperCase() || 'C'
    );
}
</script>

<template>
    <Head title="Trang chủ" />

    <div
        class="mx-auto flex w-full max-w-[1320px] flex-col gap-7 px-4 py-6 md:px-10 md:py-9"
    >
        <section class="flex flex-wrap items-start gap-x-8 gap-y-4">
            <div class="grid flex-1 basis-64 gap-1.5">
                <p class="text-sm text-muted-foreground">{{ today }}</p>
                <h1
                    class="text-[32px] leading-tight font-medium tracking-tight"
                >
                    Xin chào, {{ firstName }}
                </h1>
            </div>
            <div class="grid flex-[3] basis-[420px] gap-2">
                <form
                    role="search"
                    class="flex h-[60px] items-center gap-3.5 rounded-[14px] border-[1.5px] border-[#bfe6d8] bg-card px-5 shadow-[0_8px_24px_rgba(10,138,108,0.08)] focus-within:border-primary"
                    @submit.prevent="submitSearch"
                >
                    <Search class="size-[22px] shrink-0 text-primary" />
                    <input
                        v-model="query"
                        type="search"
                        aria-label="Tìm giấy tờ"
                        aria-describedby="search-hint"
                        placeholder="Tìm giấy tờ, công ty, dự án"
                        class="min-w-0 flex-1 bg-transparent text-[17px] font-light outline-none placeholder:text-[#7b8995]"
                    />
                </form>
                <p id="search-hint" class="px-1 text-sm text-muted-foreground">
                    Gõ không dấu cũng được, ví dụ: giay phep pccc cong ty a
                </p>
            </div>
        </section>

        <section aria-labelledby="notice-title" class="grid gap-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2
                    id="notice-title"
                    class="flex items-center gap-2.5 text-lg font-medium"
                >
                    <span
                        class="flex size-8 items-center justify-center rounded-[9px] bg-[#e5f0ff] text-[#2563c9]"
                    >
                        <Megaphone class="size-[18px]" />
                    </span>
                    Thông báo chung
                </h2>
                <div
                    class="flex gap-4 text-[15px] font-medium whitespace-nowrap"
                >
                    <Link :href="NoticeController.index()" class="text-primary">
                        Xem tất cả
                    </Link>
                    <button
                        type="button"
                        class="text-primary"
                        @click="noticeOpen = true"
                    >
                        Đăng tin
                    </button>
                </div>
            </div>
            <div
                v-if="notices.length"
                class="grid grid-cols-[repeat(auto-fit,minmax(320px,1fr))] gap-4"
            >
                <article
                    v-for="notice in notices"
                    :key="notice.id"
                    class="grid gap-1 rounded-[14px] border bg-card px-[18px] py-4"
                >
                    <h3 class="leading-snug font-medium">{{ notice.title }}</h3>
                    <p
                        v-if="notice.body"
                        class="line-clamp-2 text-sm leading-relaxed font-light text-[#3b4954]"
                    >
                        {{ notice.body }}
                    </p>
                    <p class="mt-0.5 text-[13px] text-muted-foreground">
                        {{ notice.author }}, {{ notice.date }}
                    </p>
                </article>
            </div>
            <p
                v-else
                class="rounded-[14px] border border-dashed bg-card px-5 py-4 text-sm text-muted-foreground"
            >
                Chưa có thông báo. Bấm "Đăng tin" để báo cho cả công ty.
            </p>
        </section>

        <div class="flex flex-wrap items-start gap-6">
            <div class="flex min-w-0 flex-[999] basis-[480px] flex-col gap-6">
                <section
                    aria-label="Truy cập nhanh"
                    class="grid grid-cols-[repeat(auto-fit,minmax(280px,1fr))] gap-4"
                >
                    <div
                        class="grid content-start gap-4 rounded-2xl bg-primary p-[22px] text-primary-foreground shadow-[0_12px_28px_rgba(10,138,108,0.2)]"
                    >
                        <div class="flex items-baseline justify-between gap-3">
                            <h2 class="text-xl font-medium tracking-tight">
                                <Link
                                    :href="FolderController.show(truongVinh.id)"
                                >
                                    {{ truongVinh.name }}
                                </Link>
                            </h2>
                            <span class="text-sm font-light text-[#d3f5e9]">
                                {{ truongVinh.children.length }} thư mục
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-x-2 gap-y-1.5">
                            <Link
                                v-for="child in truongVinh.children"
                                :key="child.id"
                                :href="FolderController.show(child.id)"
                                class="flex min-h-11 items-center justify-between gap-2 rounded-[10px] bg-white/14 px-3 transition-colors hover:bg-white/22"
                            >
                                <span class="truncate">{{ child.name }}</span>
                                <span
                                    class="text-[13px] font-light text-[#d3f5e9]"
                                >
                                    {{ child.count }}
                                </span>
                            </Link>
                        </div>
                    </div>

                    <div
                        class="grid content-start gap-2.5 rounded-2xl border bg-card px-[22px] pt-[22px] pb-3.5"
                    >
                        <div class="flex items-baseline justify-between gap-3">
                            <h2 class="text-xl font-medium tracking-tight">
                                Công ty
                            </h2>
                            <Link
                                :href="FolderController.show(companies.rootId)"
                                class="text-sm font-medium text-primary"
                            >
                                Xem tất cả
                            </Link>
                        </div>
                        <div v-if="companies.items.length" class="grid">
                            <Link
                                v-for="(company, index) in companies.items"
                                :key="company.id"
                                :href="FolderController.show(company.id)"
                                class="flex min-h-[52px] items-center gap-3.5 border-t border-[#eef2f4] px-1"
                            >
                                <span
                                    :class="[
                                        'flex size-[34px] items-center justify-center rounded-[9px] text-sm font-semibold',
                                        companyTones[
                                            index % companyTones.length
                                        ],
                                    ]"
                                >
                                    {{ initial(company.name) }}
                                </span>
                                <span class="flex-1 truncate font-medium">{{
                                    company.name
                                }}</span>
                                <span
                                    class="text-sm font-light text-muted-foreground"
                                >
                                    {{ company.projects }} dự án
                                </span>
                                <ChevronRight
                                    class="size-[18px] text-[#8c9aa5]"
                                />
                            </Link>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            Chưa có công ty nào.
                        </p>
                    </div>
                </section>

                <section class="grid gap-3">
                    <h2 class="text-lg font-medium">Mở gần đây</h2>
                    <div
                        v-if="recent.length"
                        class="rounded-2xl border bg-card px-2 py-1.5"
                    >
                        <Link
                            v-for="file in recent"
                            :key="file.id"
                            :href="DocumentController.show(file.id)"
                            class="flex min-h-16 items-center gap-4 rounded-[10px] px-3 py-2 transition-colors hover:bg-background"
                        >
                            <FileBadge :extension="file.extension" />
                            <span class="grid min-w-0 flex-1 gap-0.5">
                                <span class="truncate font-medium">{{
                                    file.name
                                }}</span>
                                <span
                                    class="truncate text-[13px] font-light text-muted-foreground"
                                >
                                    {{ file.path }}
                                </span>
                            </span>
                            <span class="hidden shrink-0 text-right sm:grid">
                                <span class="text-sm">{{
                                    file.updatedBy
                                }}</span>
                                <span
                                    class="text-[13px] font-light text-muted-foreground"
                                >
                                    {{ file.updatedAt }}
                                </span>
                            </span>
                        </Link>
                    </div>
                    <p
                        v-else
                        class="rounded-2xl border border-dashed bg-card px-5 py-6 text-sm text-muted-foreground"
                    >
                        File bạn mở sẽ hiện ở đây để lần sau tìm lại nhanh.
                    </p>
                </section>
            </div>

            <div
                class="flex w-full flex-1 basis-[320px] flex-col gap-5 lg:max-w-[380px]"
            >
                <TodoPanel :todos="todos" />
                <RecurringPanel
                    :week="recurring.week"
                    :upcoming="recurring.upcoming"
                />
            </div>
        </div>
    </div>

    <NoticeDialog v-model:open="noticeOpen" />
</template>
