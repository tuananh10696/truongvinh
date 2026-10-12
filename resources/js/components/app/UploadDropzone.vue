<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CloudUpload } from '@lucide/vue';
import { useDropZone } from '@vueuse/core';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import { cn } from '@/lib/utils';

const props = defineProps<{
    folderId: number;
    folderName: string;
}>();

const zone = ref<HTMLElement | null>(null);
const input = ref<HTMLInputElement | null>(null);
const progress = ref<number | null>(null);

function upload(files: File[] | FileList | null) {
    const list = files ? Array.from(files) : [];

    if (list.length === 0 || progress.value !== null) {
        return;
    }

    progress.value = 0;

    router.post(
        DocumentController.store.url(props.folderId),
        { files: list },
        {
            forceFormData: true,
            preserveScroll: true,
            onProgress: (event) => {
                progress.value = event?.percentage ?? null;
            },
            onError: (errors) => {
                toast.error(
                    Object.values(errors)[0] ?? 'Không tải lên được file.',
                );
            },
            onFinish: () => {
                progress.value = null;

                if (input.value) {
                    input.value.value = '';
                }
            },
        },
    );
}

const { isOverDropZone } = useDropZone(zone, { onDrop: upload });

defineExpose({ pick: () => input.value?.click() });
</script>

<template>
    <div
        ref="zone"
        :class="
            cn(
                'flex flex-wrap items-center gap-4 rounded-2xl border-[1.5px] border-dashed px-5 py-4 transition-colors',
                isOverDropZone
                    ? 'border-primary bg-accent'
                    : 'border-[#b7c4cc] bg-[#f7f9fa]',
            )
        "
    >
        <span
            class="flex size-11 items-center justify-center rounded-xl bg-accent text-primary"
        >
            <CloudUpload class="size-5" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="font-medium">
                <template v-if="progress !== null">
                    Đang tải lên... {{ progress }}%
                </template>
                <template v-else>
                    Kéo thả file vào đây để tải lên thư mục {{ folderName }}
                </template>
            </p>
            <p class="text-sm font-light text-muted-foreground">
                Hoặc
                <button
                    type="button"
                    class="font-medium text-primary underline-offset-4 hover:underline"
                    @click="input?.click()"
                >
                    chọn file từ máy</button
                >. Nhận file Word, Excel, PDF và ảnh.
            </p>
        </div>
        <input
            ref="input"
            type="file"
            multiple
            class="sr-only"
            aria-label="Chọn file để tải lên"
            @change="upload(($event.target as HTMLInputElement).files)"
        />
    </div>
</template>
