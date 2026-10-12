<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Download, Eye, Undo2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import DocumentVersionController from '@/actions/App/Http/Controllers/DocumentVersionController';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { formatSize } from '@/lib/format';
import type { VersionItem } from '@/types';

const props = defineProps<{
    documentId: number | null;
    documentName: string;
}>();

const open = defineModel<boolean>('open', { default: false });
const versions = ref<VersionItem[]>([]);
const loading = ref(false);
const confirming = ref<number | null>(null);
const restoring = ref(false);

async function load() {
    if (!props.documentId) {
        return;
    }

    loading.value = true;

    try {
        const response = await fetch(
            DocumentVersionController.index.url(props.documentId),
            { headers: { Accept: 'application/json' } },
        );
        versions.value = (await response.json()).versions;
    } finally {
        loading.value = false;
    }
}

watch(open, (isOpen) => {
    confirming.value = null;

    if (isOpen) {
        void load();
    }
});

function restore(version: VersionItem) {
    if (!props.documentId) {
        return;
    }

    restoring.value = true;
    router.post(
        DocumentVersionController.restore.url({
            document: props.documentId,
            version: version.id,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                confirming.value = null;
                void load();
            },
            onFinish: () => (restoring.value = false),
        },
    );
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent class="w-full gap-0 sm:max-w-[480px]">
            <SheetHeader class="gap-2 border-b p-6">
                <SheetTitle class="text-xl font-medium">
                    Lịch sử chỉnh sửa
                </SheetTitle>
                <SheetDescription class="leading-relaxed">
                    {{ documentName }}. Mỗi lần lưu, hệ thống giữ lại một bản.
                    Bạn có thể tải về hoặc khôi phục bất kỳ bản nào.
                </SheetDescription>
            </SheetHeader>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                <div v-if="loading && versions.length === 0" class="grid gap-4">
                    <Skeleton v-for="n in 3" :key="n" class="h-20 w-full" />
                </div>

                <ol v-else class="grid">
                    <li
                        v-for="version in versions"
                        :key="version.id"
                        class="grid grid-cols-[20px_minmax(0,1fr)] gap-x-4"
                    >
                        <div class="flex flex-col items-center">
                            <span
                                :class="[
                                    'mt-5 size-3 rounded-full ring-4',
                                    version.isCurrent
                                        ? 'bg-primary ring-accent'
                                        : 'bg-[#93a3ae] ring-[#eef2f4]',
                                ]"
                            />
                            <span class="mt-1.5 w-0.5 flex-1 bg-border" />
                        </div>
                        <div class="grid gap-2 py-3.5 pb-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-base font-medium">
                                    {{ version.when }}
                                </span>
                                <span
                                    v-if="version.isCurrent"
                                    class="rounded-full bg-accent px-2.5 py-0.5 text-xs font-medium text-accent-foreground"
                                >
                                    Bản hiện tại
                                </span>
                            </div>
                            <p class="text-sm text-[#3b4954]">
                                {{ version.who }} {{ version.what }}, bản
                                {{ version.versionNo }},
                                {{ formatSize(version.size) }}
                            </p>

                            <div
                                v-if="confirming === version.id"
                                class="grid gap-3 rounded-xl border border-[#bfe0d6] bg-[#f2f8f6] p-4"
                            >
                                <p class="font-medium">
                                    Khôi phục bản lúc {{ version.when }}?
                                </p>
                                <p class="text-sm font-light text-[#3b4954]">
                                    Bản hiện tại vẫn được giữ trong lịch sử,
                                    không bị mất.
                                </p>
                                <div class="flex gap-2">
                                    <Button
                                        variant="outline"
                                        @click="confirming = null"
                                    >
                                        Huỷ
                                    </Button>
                                    <Button
                                        :disabled="restoring"
                                        @click="restore(version)"
                                    >
                                        Khôi phục
                                    </Button>
                                </div>
                            </div>

                            <div v-else class="flex flex-wrap gap-2">
                                <Button variant="outline" size="sm" as-child>
                                    <a :href="version.downloadUrl">
                                        <Download />
                                        Tải bản này
                                    </a>
                                </Button>
                                <Button
                                    v-if="!version.isCurrent"
                                    variant="secondary"
                                    size="sm"
                                    class="bg-accent text-accent-foreground hover:bg-accent/80"
                                    @click="confirming = version.id"
                                >
                                    <Undo2 />
                                    Khôi phục bản này
                                </Button>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 text-sm text-muted-foreground"
                                >
                                    <Eye class="size-4" />
                                    Đang dùng bản này
                                </span>
                            </div>
                        </div>
                    </li>
                </ol>
            </div>
        </SheetContent>
    </Sheet>
</template>
