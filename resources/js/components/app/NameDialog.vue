<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
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

/**
 * Hộp thoại nhập một cái tên: tạo thư mục, thêm công ty, thêm dự án, đổi tên.
 */
const props = withDefaults(
    defineProps<{
        title: string;
        label: string;
        submitLabel: string;
        action: { url: string; method: 'post' | 'patch' };
        initialValue?: string;
        description?: string;
        placeholder?: string;
    }>(),
    { initialValue: '', description: undefined, placeholder: undefined },
);

const open = defineModel<boolean>('open', { default: false });
const form = useForm({ name: props.initialValue });
const input = ref<InstanceType<typeof Input> | null>(null);

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.name = props.initialValue;
    }
});

function submit() {
    form.submit(props.action.method, props.action.url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{ title }}</DialogTitle>
                    <DialogDescription v-if="description">
                        {{ description }}
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="name-dialog-input">{{ label }}</Label>
                    <Input
                        id="name-dialog-input"
                        ref="input"
                        v-model="form.name"
                        v-focus
                        class="h-11"
                        :placeholder="placeholder"
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
                        {{ submitLabel }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
