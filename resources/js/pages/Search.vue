<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Folder, Search } from '@lucide/vue';
import { ref } from 'vue';
import FolderController from '@/actions/App/Http/Controllers/FolderController';
import DocumentList from '@/components/app/DocumentList.vue';
import { search } from '@/routes';
import type { DocumentItem, FolderKind } from '@/types';

const props = defineProps<{
    q: string;
    documents: DocumentItem[];
    folders: Array<{
        id: number;
        name: string;
        kind: FolderKind;
        path: string | null;
    }>;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Tìm kiếm', href: search() }] },
});

const query = ref(props.q);

function submit() {
    router.get(
        search.url({ query: { q: query.value.trim() } }),
        {},
        { preserveState: true },
    );
}
</script>

<template>
    <Head title="Tìm kiếm" />

    <div
        class="mx-auto flex w-full max-w-[1100px] flex-col gap-6 px-4 py-6 md:px-10 md:py-8"
    >
        <form
            role="search"
            class="flex h-[60px] items-center gap-3.5 rounded-[14px] border-[1.5px] border-[#bfe6d8] bg-card px-5 focus-within:border-primary"
            @submit.prevent="submit"
        >
            <Search class="size-[22px] text-primary" />
            <input
                v-model="query"
                v-focus
                type="search"
                aria-label="Tìm giấy tờ"
                placeholder="Tìm giấy tờ, công ty, dự án (gõ không dấu cũng được)"
                class="min-w-0 flex-1 bg-transparent text-[17px] font-light outline-none"
            />
        </form>

        <template v-if="q.length >= 2">
            <p class="text-muted-foreground">
                Tìm thấy {{ folders.length }} thư mục và
                {{ documents.length }} file cho "{{ q }}"
            </p>

            <section v-if="folders.length" class="grid gap-3">
                <h2 class="text-lg font-medium">Thư mục, công ty, dự án</h2>
                <div
                    class="grid grid-cols-[repeat(auto-fill,minmax(260px,1fr))] gap-3"
                >
                    <Link
                        v-for="folder in folders"
                        :key="folder.id"
                        :href="FolderController.show(folder.id)"
                        class="flex items-center gap-3 rounded-2xl border bg-card px-4 py-3 hover:border-primary/40"
                    >
                        <Folder
                            class="size-7 shrink-0 fill-amber-200 text-amber-600"
                            :stroke-width="1.5"
                        />
                        <span class="grid min-w-0">
                            <span class="truncate font-medium">{{
                                folder.name
                            }}</span>
                            <span
                                v-if="folder.path"
                                class="truncate text-[13px] font-light text-muted-foreground"
                            >
                                {{ folder.path }}
                            </span>
                        </span>
                    </Link>
                </div>
            </section>

            <section v-if="documents.length" class="grid gap-3">
                <h2 class="text-lg font-medium">File</h2>
                <div class="rounded-2xl border bg-card p-2">
                    <DocumentList :documents="documents" show-path />
                </div>
            </section>

            <p
                v-if="!folders.length && !documents.length"
                class="rounded-2xl border border-dashed bg-card px-6 py-10 text-center text-muted-foreground"
            >
                Không tìm thấy kết quả. Thử gõ ít chữ hơn, ví dụ chỉ tên công ty
                hoặc một từ trong tên file.
            </p>
        </template>
        <p v-else class="text-muted-foreground">Gõ ít nhất 2 ký tự để tìm.</p>
    </div>
</template>
