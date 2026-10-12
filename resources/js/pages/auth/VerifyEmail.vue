<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Xác nhận email',
        description:
            'Hãy bấm vào link trong email hệ thống vừa gửi cho bạn để xác nhận địa chỉ email.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Xác nhận email" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        Đã gửi lại link xác nhận tới email của bạn.
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" variant="secondary">
            <Spinner v-if="processing" />
            Gửi lại email xác nhận
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            Đăng xuất
        </TextLink>
    </Form>
</template>
