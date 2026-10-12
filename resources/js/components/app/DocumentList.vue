<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    Download,
    Ellipsis,
    History,
    PencilLine,
    RotateCcw,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import TrashController from '@/actions/App/Http/Controllers/TrashController';
import FileBadge from '@/components/app/FileBadge.vue';
import NameDialog from '@/components/app/NameDialog.vue';
import VersionHistorySheet from '@/components/app/VersionHistorySheet.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatSize } from '@/lib/format';
import type { DocumentItem } from '@/types';

withDefaults(
    defineProps<{
        documents: DocumentItem[];
        showPath?: boolean;
        mode?: 'normal' | 'trash';
    }>(),
    { showPath: false, mode: 'normal' },
);

const renaming = ref<DocumentItem | null>(null);
const renameOpen = ref(false);
const historyFor = ref<DocumentItem | null>(null);
const historyOpen = ref(false);

// Mở hộp thoại sau khi menu đã đóng hẳn, nếu không menu trả focus sẽ làm hộp thoại đóng theo.
function afterMenuCloses(callback: () => void) {
    setTimeout(callback, 0);
}

function openRename(document: DocumentItem) {
    afterMenuCloses(() => {
        renaming.value = document;
        renameOpen.value = true;
    });
}

function openHistory(document: DocumentItem) {
    afterMenuCloses(() => {
        historyFor.value = document;
        historyOpen.value = true;
    });
}

function trash(document: DocumentItem) {
    router.delete(DocumentController.destroy.url(document.id), {
        preserveScroll: true,
    });
}

function restore(document: DocumentItem) {
    router.post(
        TrashController.restore.url(document.id),
        {},
        { preserveScroll: true },
    );
}

function baseName(name: string): string {
    return name.replace(/\.[^.]+$/, '');
}
</script>

<template>
    <!-- Điện thoại: dạng thẻ, bấm vào là xem -->
    <ul class="grid gap-2 p-2 md:hidden">
        <li v-for="document in documents" :key="`m-${document.id}`">
            <component
                :is="mode === 'trash' ? 'div' : Link"
                :href="
                    mode === 'trash'
                        ? undefined
                        : DocumentController.show(document.id)
                "
                class="flex min-h-[72px] items-center gap-3.5 rounded-xl border bg-card p-3"
            >
                <FileBadge :extension="document.extension" />
                <span class="grid min-w-0 flex-1">
                    <span class="leading-snug font-medium">{{
                        document.name
                    }}</span>
                    <span class="text-[13px] font-light text-muted-foreground">
                        {{
                            [
                                document.updatedBy,
                                mode === 'trash'
                                    ? document.deletedAt
                                    : document.updatedAt,
                            ]
                                .filter(Boolean)
                                .join(', ')
                        }}
                    </span>
                </span>
                <Button
                    v-if="mode === 'trash'"
                    variant="secondary"
                    size="sm"
                    class="bg-accent text-accent-foreground"
                    @click="restore(document)"
                >
                    Khôi phục
                </Button>
            </component>
        </li>
    </ul>

    <div class="hidden overflow-x-auto md:block">
        <div class="min-w-[720px]">
            <div
                class="grid grid-cols-[minmax(0,3fr)_minmax(0,1.3fr)_minmax(0,1.2fr)_minmax(0,0.8fr)_140px] gap-4 border-b px-4 py-2.5 text-[13px] text-muted-foreground"
            >
                <span>Tên file</span>
                <span>{{
                    mode === 'trash' ? 'Người xoá' : 'Người sửa gần nhất'
                }}</span>
                <span>{{ mode === 'trash' ? 'Ngày xoá' : 'Cập nhật' }}</span>
                <span>Dung lượng</span>
                <span class="sr-only">Thao tác</span>
            </div>

            <div
                v-for="document in documents"
                :key="document.id"
                class="grid min-h-[68px] grid-cols-[minmax(0,3fr)_minmax(0,1.3fr)_minmax(0,1.2fr)_minmax(0,0.8fr)_140px] items-center gap-4 border-b border-[#f0f3f5] px-4 py-1.5 last:border-b-0"
            >
                <component
                    :is="mode === 'trash' ? 'div' : Link"
                    :href="
                        mode === 'trash'
                            ? undefined
                            : DocumentController.show(document.id)
                    "
                    class="flex min-w-0 items-center gap-3.5 rounded-lg"
                >
                    <FileBadge :extension="document.extension" />
                    <span class="grid min-w-0">
                        <span class="truncate font-medium">{{
                            document.name
                        }}</span>
                        <span
                            v-if="showPath && document.path"
                            class="truncate text-[13px] font-light text-muted-foreground"
                        >
                            {{ document.path }}
                        </span>
                    </span>
                </component>
                <span class="truncate">{{ document.updatedBy ?? '' }}</span>
                <span class="font-light text-muted-foreground">{{
                    mode === 'trash' ? document.deletedAt : document.updatedAt
                }}</span>
                <span class="font-light text-muted-foreground">{{
                    formatSize(document.size)
                }}</span>

                <span class="flex justify-end gap-1.5">
                    <template v-if="mode === 'trash'">
                        <Button
                            variant="secondary"
                            class="bg-accent text-accent-foreground hover:bg-accent/80"
                            @click="restore(document)"
                        >
                            <RotateCcw />
                            Khôi phục
                        </Button>
                    </template>
                    <template v-else>
                        <Button
                            v-if="document.viewable"
                            variant="secondary"
                            class="h-10 bg-accent px-4 text-accent-foreground hover:bg-accent/80"
                            as-child
                        >
                            <Link :href="DocumentController.show(document.id)">
                                {{ document.editable ? 'Mở' : 'Xem' }}
                            </Link>
                        </Button>
                        <Button
                            v-else
                            variant="secondary"
                            class="h-10 bg-accent px-4 text-accent-foreground hover:bg-accent/80"
                            as-child
                        >
                            <a
                                :href="
                                    DocumentController.download.url(document.id)
                                "
                            >
                                Tải về
                            </a>
                        </Button>
                        <DropdownMenu :modal="false">
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon-lg"
                                    aria-label="Thao tác khác"
                                >
                                    <Ellipsis class="size-5" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-52">
                                <DropdownMenuItem as-child>
                                    <a
                                        :href="
                                            DocumentController.download.url(
                                                document.id,
                                            )
                                        "
                                    >
                                        <Download />
                                        Tải về máy
                                    </a>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    @select="openHistory(document)"
                                >
                                    <History />
                                    Lịch sử chỉnh sửa
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    @select="openRename(document)"
                                >
                                    <PencilLine />
                                    Đổi tên
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    variant="destructive"
                                    @select="trash(document)"
                                >
                                    <Trash2 />
                                    Chuyển vào Thùng rác
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </template>
                </span>
            </div>
        </div>
    </div>

    <NameDialog
        v-if="renaming"
        v-model:open="renameOpen"
        title="Đổi tên file"
        label="Tên mới"
        submit-label="Lưu tên mới"
        :initial-value="baseName(renaming.name)"
        :action="{
            url: DocumentController.update.url(renaming.id),
            method: 'patch',
        }"
    />

    <VersionHistorySheet
        v-model:open="historyOpen"
        :document-id="historyFor?.id ?? null"
        :document-name="historyFor?.name ?? ''"
    />
</template>
