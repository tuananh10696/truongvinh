<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronRight,
    FilePlus2,
    Folder,
    FolderPlus,
    Pin,
    PinOff,
    Plus,
    Upload,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import DirectoryController from '@/actions/App/Http/Controllers/DirectoryController';
import FolderController from '@/actions/App/Http/Controllers/FolderController';
import PinController from '@/actions/App/Http/Controllers/PinController';
import CreateFileDialog from '@/components/app/CreateFileDialog.vue';
import DocumentList from '@/components/app/DocumentList.vue';
import NameDialog from '@/components/app/NameDialog.vue';
import UploadDropzone from '@/components/app/UploadDropzone.vue';
import { Button } from '@/components/ui/button';
import { useFolderBreadcrumbs } from '@/composables/useFolderBreadcrumbs';
import type { Crumb, DocumentItem, FolderKind, FolderSummary } from '@/types';

type Child = {
    id: number;
    name: string;
    kind: FolderKind;
    childrenCount: number;
    documentsCount: number;
};

const props = defineProps<{
    folder: FolderSummary;
    breadcrumbs: Crumb[];
    children: Child[];
    documents: DocumentItem[];
    blankTypes: string[];
}>();

useFolderBreadcrumbs(() => props.breadcrumbs);

const nameOpen = ref(false);
const createOpen = ref(false);
const dropzone = ref<InstanceType<typeof UploadDropzone> | null>(null);

const canHoldDocuments = computed(
    () =>
        !['truong_vinh', 'company_root', 'company'].includes(props.folder.kind),
);
const canPin = computed(() => props.folder.kind !== 'company_root');

/** Nút thêm phù hợp với từng cấp trong cây. */
const addAction = computed(() => {
    switch (props.folder.kind) {
        case 'company_root':
            return {
                button: 'Thêm công ty',
                title: 'Thêm công ty khách hàng',
                label: 'Tên công ty',
                submit: 'Thêm công ty',
                description: undefined,
                action: DirectoryController.storeCompany(),
            };
        case 'company':
            return {
                button: 'Thêm dự án',
                title: `Thêm dự án cho ${props.folder.name}`,
                label: 'Tên dự án',
                submit: 'Thêm dự án',
                description:
                    'Dự án mới được tạo sẵn 7 thư mục theo mẫu (Đầu tư, HĐDV (TV), PCCC...).',
                action: DirectoryController.storeProject(
                    props.folder.companyId ?? 0,
                ),
            };
        default:
            return {
                button: 'Tạo thư mục',
                title: 'Tạo thư mục con',
                label: 'Tên thư mục',
                submit: 'Tạo thư mục',
                description: undefined,
                action: FolderController.store(props.folder.id),
            };
    }
});

const subtitle = computed(() => {
    if (props.folder.kind === 'company_root') {
        return `${props.children.length} công ty khách hàng`;
    }

    if (props.folder.kind === 'company') {
        return `${props.children.length} dự án`;
    }

    if (props.folder.kind === 'truong_vinh') {
        return `${props.children.length} thư mục`;
    }

    const parts = [];

    if (props.children.length) {
        parts.push(`${props.children.length} thư mục`);
    }

    parts.push(`${props.documents.length} file`);

    return parts.join(', ');
});

function childMeta(child: Child): string {
    if (child.kind === 'company') {
        return `${child.childrenCount} dự án`;
    }

    if (child.kind === 'project') {
        return `${child.childrenCount} thư mục`;
    }

    return `${child.documentsCount} file`;
}

function togglePin() {
    router.post(
        PinController.toggle.url(props.folder.id),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="folder.name" />

    <div
        class="mx-auto flex w-full max-w-[1240px] flex-col gap-6 px-4 py-6 md:px-10 md:py-8"
    >
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="grid gap-1.5">
                <h1
                    class="text-[32px] leading-tight font-medium tracking-tight"
                >
                    {{ folder.name }}
                </h1>
                <p class="font-light text-muted-foreground">{{ subtitle }}</p>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <Button
                    v-if="canPin"
                    variant="ghost"
                    size="lg"
                    class="h-11"
                    :aria-pressed="folder.isPinned"
                    @click="togglePin"
                >
                    <component :is="folder.isPinned ? PinOff : Pin" />
                    {{ folder.isPinned ? 'Bỏ ghim' : 'Ghim' }}
                </Button>
                <Button
                    :variant="canHoldDocuments ? 'outline' : 'default'"
                    size="lg"
                    class="h-11"
                    @click="nameOpen = true"
                >
                    <component :is="canHoldDocuments ? FolderPlus : Plus" />
                    {{ addAction.button }}
                </Button>
                <div v-if="canHoldDocuments" class="hidden gap-2.5 md:flex">
                    <Button
                        variant="outline"
                        size="lg"
                        class="h-11"
                        @click="createOpen = true"
                    >
                        <FilePlus2 />
                        Tạo file mới
                    </Button>
                    <Button size="lg" class="h-11" @click="dropzone?.pick()">
                        <Upload />
                        Tải file lên
                    </Button>
                </div>
            </div>
        </div>

        <section
            v-if="children.length"
            aria-label="Thư mục con"
            class="grid grid-cols-[repeat(auto-fill,minmax(240px,1fr))] gap-3"
        >
            <Link
                v-for="child in children"
                :key="child.id"
                :href="FolderController.show(child.id)"
                class="flex min-h-[72px] items-center gap-3.5 rounded-2xl border bg-card px-4 py-3 transition-colors hover:border-primary/40 hover:bg-accent/40"
            >
                <span
                    v-if="child.kind === 'company'"
                    class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-accent text-sm font-semibold text-accent-foreground"
                >
                    {{
                        child.name.replace(/^Công ty\s*/i, '').charAt(0) || 'C'
                    }}
                </span>
                <Folder
                    v-else
                    class="size-8 shrink-0 fill-amber-200 text-amber-600"
                    :stroke-width="1.5"
                />
                <span class="grid min-w-0 flex-1">
                    <span class="truncate font-medium">{{ child.name }}</span>
                    <span class="text-[13px] font-light text-muted-foreground">
                        {{ childMeta(child) }}
                    </span>
                </span>
                <ChevronRight class="size-4 text-muted-foreground" />
            </Link>
        </section>

        <p
            v-else-if="!canHoldDocuments"
            class="rounded-2xl border border-dashed bg-card px-6 py-10 text-center text-muted-foreground"
        >
            Chưa có mục nào. Bấm "{{ addAction.button }}" để bắt đầu.
        </p>

        <section
            v-if="canHoldDocuments"
            class="rounded-2xl border bg-card p-2 pb-5"
        >
            <DocumentList v-if="documents.length" :documents="documents" />
            <p
                v-else
                class="mx-2 mt-2 rounded-xl bg-muted px-4 py-6 text-center text-muted-foreground"
            >
                Thư mục chưa có file. Kéo thả file vào khung bên dưới, hoặc bấm
                "Tạo file mới".
            </p>
            <div class="mx-4 mt-4 hidden md:block">
                <UploadDropzone
                    ref="dropzone"
                    :folder-id="folder.id"
                    :folder-name="folder.name"
                />
            </div>
        </section>
    </div>

    <NameDialog
        v-model:open="nameOpen"
        :title="addAction.title"
        :label="addAction.label"
        :submit-label="addAction.submit"
        :description="addAction.description"
        :action="addAction.action"
    />

    <CreateFileDialog
        v-if="canHoldDocuments"
        v-model:open="createOpen"
        :folder-id="folder.id"
        :folder-name="folder.name"
        :types="blankTypes"
    />
</template>
