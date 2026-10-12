<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FilePlus2, FolderOpen, Pin, PinOff, Upload } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import FolderController from '@/actions/App/Http/Controllers/FolderController';
import PinController from '@/actions/App/Http/Controllers/PinController';
import CreateFileDialog from '@/components/app/CreateFileDialog.vue';
import DocumentList from '@/components/app/DocumentList.vue';
import UploadDropzone from '@/components/app/UploadDropzone.vue';
import { Button } from '@/components/ui/button';
import { useFolderBreadcrumbs } from '@/composables/useFolderBreadcrumbs';
import { cn } from '@/lib/utils';
import type { Crumb, DocumentItem, FolderSummary } from '@/types';

type Tab = { id: number; name: string; documentsCount: number };

const props = defineProps<{
    project: FolderSummary;
    breadcrumbs: Crumb[];
    tabs: Tab[];
    activeTab: { id: number; name: string } | null;
    totalDocuments: number;
    documents: DocumentItem[];
    blankTypes: string[];
}>();

useFolderBreadcrumbs(() => props.breadcrumbs);

const createOpen = ref(false);
const tabNav = ref<HTMLElement | null>(null);

// Trên điện thoại tab xếp thành một hàng cuộn ngang: cuộn tab đang chọn vào giữa.
onMounted(() => {
    tabNav.value
        ?.querySelector('[aria-current="page"]')
        ?.scrollIntoView({ inline: 'center', block: 'nearest' });
});
const dropzone = ref<InstanceType<typeof UploadDropzone> | null>(null);

function togglePin() {
    router.post(
        PinController.toggle.url(props.project.id),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="`${project.name} - ${project.parentName ?? ''}`" />

    <div
        class="mx-auto flex w-full max-w-[1240px] flex-col gap-6 px-4 py-6 md:px-10 md:py-8"
    >
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="grid gap-1.5">
                <h1
                    class="text-[32px] leading-tight font-medium tracking-tight"
                >
                    {{ project.name }}
                </h1>
                <p class="font-light text-muted-foreground">
                    {{ project.parentName }}, {{ totalDocuments }} file trong
                    {{ tabs.length }} thư mục
                </p>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <Button
                    variant="ghost"
                    size="lg"
                    class="h-11"
                    :aria-pressed="project.isPinned"
                    @click="togglePin"
                >
                    <component :is="project.isPinned ? PinOff : Pin" />
                    {{ project.isPinned ? 'Bỏ ghim' : 'Ghim' }}
                </Button>
                <!-- Điện thoại chỉ xem: ẩn các thao tác tạo, tải lên -->
                <div v-if="activeTab" class="hidden gap-2.5 md:flex">
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

        <div>
            <!-- 7 thư mục của dự án hiển thị như tab của bìa hồ sơ -->
            <nav
                ref="tabNav"
                aria-label="Thư mục của dự án"
                class="relative z-10 flex [scrollbar-width:none] gap-1 overflow-x-auto px-2 md:flex-wrap md:overflow-visible [&::-webkit-scrollbar]:hidden"
            >
                <Link
                    v-for="tab in tabs"
                    :key="tab.id"
                    :href="FolderController.show(tab.id)"
                    preserve-scroll
                    :aria-current="
                        tab.id === activeTab?.id ? 'page' : undefined
                    "
                    :class="
                        cn(
                            'flex shrink-0 items-center gap-2.5 rounded-t-xl border px-4 text-[15px] whitespace-nowrap transition-colors',
                            tab.id === activeTab?.id
                                ? '-mb-px h-[50px] border-b-card bg-card font-medium text-foreground'
                                : 'mt-1 h-[46px] border-b-0 bg-[#e7ecef] text-[#3b4954] hover:bg-[#dde4e8]',
                        )
                    "
                >
                    <FolderOpen
                        v-if="tab.id === activeTab?.id"
                        class="size-[18px] text-primary"
                    />
                    {{ tab.name }}
                    <span
                        :class="
                            cn(
                                'flex h-[22px] min-w-[22px] items-center justify-center rounded-full px-1.5 text-xs font-medium',
                                tab.id === activeTab?.id
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-[#d5dde2] text-[#3b4954]',
                            )
                        "
                    >
                        {{ tab.documentsCount }}
                    </span>
                </Link>
            </nav>

            <section
                v-if="activeTab"
                class="rounded-2xl border bg-card p-2 pb-5 shadow-[0_12px_32px_rgba(15,29,38,0.05)]"
            >
                <p class="px-4 pt-3.5 pb-2.5 text-muted-foreground">
                    {{ documents.length }} file trong thư mục
                    <strong class="font-medium text-foreground">{{
                        activeTab.name
                    }}</strong>
                </p>

                <DocumentList v-if="documents.length" :documents="documents" />
                <p
                    v-else
                    class="mx-4 rounded-xl bg-muted px-4 py-6 text-center text-muted-foreground"
                >
                    Thư mục chưa có file. Kéo thả file vào khung bên dưới, hoặc
                    bấm "Tạo file mới".
                </p>

                <div class="mx-4 mt-4 hidden md:block">
                    <UploadDropzone
                        ref="dropzone"
                        :folder-id="activeTab.id"
                        :folder-name="activeTab.name"
                    />
                </div>
            </section>
        </div>
    </div>

    <CreateFileDialog
        v-if="activeTab"
        v-model:open="createOpen"
        :folder-id="activeTab.id"
        :folder-name="activeTab.name"
        :types="blankTypes"
    />
</template>
