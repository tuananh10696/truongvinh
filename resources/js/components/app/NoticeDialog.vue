<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import NoticeController from '@/actions/App/Http/Controllers/NoticeController';
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

const open = defineModel<boolean>('open', { default: false });
const form = useForm({ title: '', body: '' });

watch(open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.clearErrors();
    }
});

function submit() {
    form.submit(NoticeController.store(), {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Đăng thông báo chung</DialogTitle>
                    <DialogDescription>
                        Mọi người trong công ty sẽ thấy thông báo này ở trang
                        chủ.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="notice-title">Tiêu đề</Label>
                    <Input
                        id="notice-title"
                        v-model="form.title"
                        class="h-11"
                        placeholder="Ví dụ: Lịch họp giao ban tuần này"
                        required
                    />
                    <InputError :message="form.errors.title" />
                </div>
                <div class="grid gap-2">
                    <Label for="notice-body">Nội dung</Label>
                    <textarea
                        id="notice-body"
                        v-model="form.body"
                        rows="4"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    />
                    <InputError :message="form.errors.body" />
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
                        Đăng thông báo
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
