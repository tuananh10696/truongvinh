<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import FileBadge from '@/components/app/FileBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const props = defineProps<{
    folderId: number;
    folderName: string;
    types: string[];
}>();

const open = defineModel<boolean>('open', { default: false });
const form = useForm({ type: 'docx', name: '' });

const labels: Record<string, string> = {
    docx: 'Word',
    xlsx: 'Excel',
};

watch(open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.clearErrors();
    }
});

function submit() {
    form.submit(DocumentController.storeBlank(props.folderId));
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Tạo file mới</DialogTitle>
                    <DialogDescription>
                        File được tạo trong thư mục {{ folderName }} và mở ra để
                        sửa ngay.
                    </DialogDescription>
                </DialogHeader>

                <fieldset class="grid gap-2">
                    <legend class="mb-2 text-sm font-medium">Loại file</legend>
                    <div class="grid grid-cols-2 gap-3">
                        <label
                            v-for="type in types"
                            :key="type"
                            :class="
                                cn(
                                    'flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition-colors',
                                    form.type === type
                                        ? 'border-primary bg-accent'
                                        : 'hover:bg-muted',
                                )
                            "
                        >
                            <input
                                v-model="form.type"
                                type="radio"
                                name="type"
                                :value="type"
                                class="sr-only"
                            />
                            <FileBadge :extension="type" size="sm" />
                            <span class="font-medium">{{
                                labels[type] ?? type
                            }}</span>
                        </label>
                    </div>
                    <InputError :message="form.errors.type" />
                </fieldset>

                <div class="grid gap-2">
                    <Label for="new-file-name">Tên file</Label>
                    <Input
                        id="new-file-name"
                        v-model="form.name"
                        class="h-11"
                        placeholder="Ví dụ: Báo giá tháng 10"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="lg"
                        @click="open = false"
                    >
                        Huỷ
                    </Button>
                    <Button type="submit" size="lg" :disabled="form.processing">
                        Tạo và mở file
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
