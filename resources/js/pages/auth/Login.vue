<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Đăng nhập',
        description: 'Dùng email và mật khẩu được quản trị hệ thống cấp.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Đăng nhập" />

    <div
        v-if="status"
        class="mb-6 rounded-xl bg-accent px-4 py-3 text-[15px] font-medium text-accent-foreground"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="grid gap-6"
    >
        <div class="grid gap-2">
            <Label for="email">Email</Label>
            <Input
                id="email"
                type="email"
                name="email"
                required
                v-focus
                :tabindex="1"
                autocomplete="email"
                placeholder="ten@truongvinh.vn"
            />
            <InputError :message="errors.email" />
        </div>

        <div class="grid gap-2">
            <div class="flex items-center justify-between">
                <Label for="password">Mật khẩu</Label>
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-[15px] font-medium"
                    :tabindex="5"
                >
                    Quên mật khẩu?
                </TextLink>
            </div>
            <PasswordInput
                id="password"
                name="password"
                required
                :tabindex="2"
                autocomplete="current-password"
                placeholder="Nhập mật khẩu"
            />
            <InputError :message="errors.password" />
        </div>

        <label
            for="remember"
            class="flex cursor-pointer items-center gap-3 text-[15px] font-medium"
        >
            <Checkbox
                id="remember"
                name="remember"
                class="size-5"
                :tabindex="3"
            />
            Ghi nhớ đăng nhập trên máy này
        </label>

        <Button
            type="submit"
            class="mt-2 w-full"
            :tabindex="4"
            :disabled="processing"
            data-test="login-button"
        >
            <Spinner v-if="processing" />
            Đăng nhập
        </Button>
    </Form>
</template>
