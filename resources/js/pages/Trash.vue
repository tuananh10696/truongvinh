<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TrashController from '@/actions/App/Http/Controllers/TrashController';
import DocumentList from '@/components/app/DocumentList.vue';
import type { DocumentItem } from '@/types';

defineProps<{ documents: DocumentItem[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Thùng rác', href: TrashController.index() }],
    },
});
</script>

<template>
    <Head title="Thùng rác" />

    <div
        class="mx-auto flex w-full max-w-[1100px] flex-col gap-6 px-4 py-6 md:px-10 md:py-8"
    >
        <div class="grid gap-1.5">
            <h1 class="text-[32px] leading-tight font-medium tracking-tight">
                Thùng rác
            </h1>
            <p class="font-light text-muted-foreground">
                File đã xoá vẫn nằm ở đây. Bấm "Khôi phục" để đưa file về chỗ
                cũ.
            </p>
        </div>

        <div v-if="documents.length" class="rounded-2xl border bg-card p-2">
            <DocumentList :documents="documents" mode="trash" show-path />
        </div>
        <p
            v-else
            class="rounded-2xl border border-dashed bg-card px-6 py-10 text-center text-muted-foreground"
        >
            Thùng rác đang trống.
        </p>
    </div>
</template>
