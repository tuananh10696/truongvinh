<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CircleAlert,
    CircleCheck,
    Download,
    Eye,
    History,
    LoaderCircle,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import FolderController from '@/actions/App/Http/Controllers/FolderController';
import FileBadge from '@/components/app/FileBadge.vue';
import VersionHistorySheet from '@/components/app/VersionHistorySheet.vue';
import { Button } from '@/components/ui/button';
import type { Crumb } from '@/types';

type DocsApiEditor = { destroyEditor: () => void };

declare global {
    interface Window {
        DocsAPI?: {
            DocEditor: new (
                elementId: string,
                config: Record<string, unknown>,
            ) => DocsApiEditor;
        };
    }
}

const props = defineProps<{
    document: {
        id: number;
        name: string;
        extension: string;
        editable: boolean;
        folderId: number;
        updatedAt: string | null;
        updatedBy: string | null;
    };
    breadcrumbs: Crumb[];
    editor: { scriptUrl: string; config: Record<string, unknown> };
    isMobile: boolean;
}>();

const status = ref<'loading' | 'ready' | 'saving' | 'saved' | 'error'>(
    'loading',
);
const savedAt = ref<string | null>(null);
const historyOpen = ref(false);
let editor: DocsApiEditor | null = null;

const path = computed(() =>
    props.breadcrumbs
        .filter((crumb) => !['root', 'company_root'].includes(crumb.kind))
        .map((crumb) => crumb.name)
        .join(' › '),
);

function now(): string {
    return new Date().toLocaleTimeString('vi-VN', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function loadScript(src: string): Promise<void> {
    return new Promise((resolve, reject) => {
        if (window.DocsAPI) {
            resolve();

            return;
        }

        const script = window.document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Không tải được bộ soạn thảo'));
        window.document.head.appendChild(script);
    });
}

onMounted(async () => {
    try {
        await loadScript(props.editor.scriptUrl);

        if (!window.DocsAPI) {
            throw new Error('DocsAPI không sẵn sàng');
        }

        editor = new window.DocsAPI.DocEditor('onlyoffice-editor', {
            ...props.editor.config,
            events: {
                onAppReady: () => (status.value = 'ready'),
                onDocumentStateChange: (event: { data: boolean }) => {
                    if (event.data) {
                        status.value = 'saving';
                    } else {
                        status.value = 'saved';
                        savedAt.value = now();
                    }
                },
                onError: () => (status.value = 'error'),
            },
        });
    } catch {
        status.value = 'error';
    }
});

onBeforeUnmount(() => {
    editor?.destroyEditor();
    editor = null;
});
</script>

<template>
    <Head :title="document.name" />

    <div class="flex h-dvh flex-col bg-[#eef3f5]">
        <header
            class="flex flex-wrap items-center gap-x-5 gap-y-3 border-b bg-card px-4 py-3 md:px-5"
        >
            <Button variant="outline" size="lg" class="h-11" as-child>
                <Link :href="FolderController.show(document.folderId)">
                    <ArrowLeft />
                    <span class="hidden sm:inline">Đóng và quay lại</span>
                    <span class="sm:hidden">Quay lại</span>
                </Link>
            </Button>

            <div class="flex min-w-0 flex-1 basis-60 items-center gap-3">
                <FileBadge :extension="document.extension" size="sm" />
                <div class="grid min-w-0 leading-tight">
                    <span class="truncate font-medium">{{
                        document.name
                    }}</span>
                    <span
                        class="truncate text-[13px] font-light text-muted-foreground"
                    >
                        {{ path }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <span
                    v-if="isMobile || !document.editable"
                    class="inline-flex h-9 items-center gap-2 rounded-full bg-muted px-3 text-sm text-muted-foreground"
                >
                    <Eye class="size-4" />
                    Chỉ xem
                </span>
                <span
                    v-else
                    role="status"
                    class="inline-flex h-9 items-center gap-2 rounded-full bg-accent px-3 text-sm text-accent-foreground"
                >
                    <template v-if="status === 'saving'">
                        <LoaderCircle class="size-4 animate-spin" />
                        Đang lưu thay đổi...
                    </template>
                    <template v-else-if="status === 'saved' && savedAt">
                        <CircleCheck class="size-4" />
                        Đã tự động lưu lúc {{ savedAt }}
                    </template>
                    <template v-else-if="status === 'error'">
                        <CircleAlert class="size-4" />
                        Có lỗi với bộ soạn thảo
                    </template>
                    <template v-else>
                        <CircleCheck class="size-4" />
                        Tự động lưu khi bạn sửa
                    </template>
                </span>

                <Button
                    variant="outline"
                    size="lg"
                    class="h-11"
                    @click="historyOpen = true"
                >
                    <History />
                    <span class="hidden md:inline">Lịch sử chỉnh sửa</span>
                </Button>
                <Button
                    variant="outline"
                    size="icon-lg"
                    class="size-11"
                    as-child
                >
                    <a
                        :href="DocumentController.download.url(document.id)"
                        aria-label="Tải về máy"
                        title="Tải về máy"
                    >
                        <Download />
                    </a>
                </Button>
            </div>
        </header>

        <main
            class="relative m-2 flex-1 overflow-hidden rounded-xl border bg-card shadow-[0_12px_32px_rgba(15,29,38,0.08)] md:m-4"
        >
            <div id="onlyoffice-editor" class="h-full w-full" />

            <div
                v-if="status === 'loading'"
                class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-card text-muted-foreground"
            >
                <LoaderCircle class="size-7 animate-spin text-primary" />
                Đang mở file...
            </div>

            <div
                v-if="status === 'error'"
                class="absolute inset-x-0 top-0 m-4 flex items-start gap-3 rounded-xl border border-[#f3c9c4] bg-[#fdf1ef] p-4 text-[#7a1d14]"
            >
                <CircleAlert class="mt-0.5 size-5 shrink-0" />
                <div class="grid gap-1">
                    <p class="font-medium">Không mở được bộ soạn thảo</p>
                    <p class="text-sm">
                        Hãy thử tải lại trang. Nếu vẫn lỗi, báo quản trị hệ
                        thống kiểm tra máy chủ ONLYOFFICE. Bạn vẫn có thể bấm
                        nút tải về để xem file trên máy.
                    </p>
                </div>
            </div>
        </main>
    </div>

    <VersionHistorySheet
        v-model:open="historyOpen"
        :document-id="document.id"
        :document-name="document.name"
    />
</template>
