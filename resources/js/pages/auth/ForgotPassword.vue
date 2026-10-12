<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Quên mật khẩu',
        description:
            'Nhập email của bạn, hệ thống sẽ gửi link để đặt lại mật khẩu.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Quên mật khẩu" />

    <div
        v-if="status"
        class="mb-6 rounded-xl bg-accent px-4 py-3 text-[15px] font-medium text-accent-foreground"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    v-focus
                    placeholder="ten@truongvinh.vn"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    type="submit"
                    class="w-full"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    Gửi link đặt lại mật khẩu
                </Button>
            </div>
        </Form>

        <div class="text-center text-[15px] text-muted-foreground">
            <TextLink :href="login()" class="font-medium">
                Quay lại trang đăng nhập
            </TextLink>
        </div>
    </div>
</template>
