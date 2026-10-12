<script setup lang="ts">
import { FolderSearch, History, PencilLine } from '@lucide/vue';

defineProps<{
    title?: string;
    description?: string;
}>();

const highlights = [
    {
        icon: FolderSearch,
        title: 'Tìm giấy tờ trong vài giây',
        text: 'Theo tên công ty, dự án hoặc tên file. Gõ không dấu cũng được.',
    },
    {
        icon: PencilLine,
        title: 'Sửa Word, Excel ngay trên trình duyệt',
        text: 'Không phải tải về rồi gửi lại. Hệ thống tự lưu khi bạn sửa.',
    },
    {
        icon: History,
        title: 'Không sợ mất bản cũ',
        text: 'Mỗi lần lưu đều giữ lại một bản, khôi phục được bất cứ lúc nào.',
    },
];
</script>

<template>
    <div class="flex min-h-svh bg-background">
        <!-- Khối thương hiệu, chỉ hiện trên màn hình rộng -->
        <aside
            class="relative hidden w-[46%] max-w-[640px] flex-col justify-between bg-primary p-12 text-primary-foreground lg:flex"
        >
            <div class="relative z-10 flex items-center gap-3">
                <span
                    class="flex size-12 items-center justify-center rounded-xl bg-white text-lg font-semibold tracking-wide text-primary"
                >
                    TV
                </span>
                <span class="grid leading-tight">
                    <span class="text-xl font-semibold">Trường Vinh</span>
                    <span class="text-[15px] text-white/80">Kho giấy tờ</span>
                </span>
            </div>

            <div class="relative z-10 grid gap-10">
                <h2
                    class="max-w-[16ch] text-[40px] leading-[1.15] font-semibold tracking-tight"
                >
                    Mọi giấy tờ của công ty, ở cùng một chỗ.
                </h2>
                <ul class="grid gap-6">
                    <li
                        v-for="item in highlights"
                        :key="item.title"
                        class="flex gap-4"
                    >
                        <span
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white/15"
                        >
                            <component :is="item.icon" class="size-5" />
                        </span>
                        <span class="grid gap-1">
                            <span class="text-[17px] font-semibold">{{
                                item.title
                            }}</span>
                            <span
                                class="max-w-[42ch] text-[15px] leading-relaxed text-white/80"
                            >
                                {{ item.text }}
                            </span>
                        </span>
                    </li>
                </ul>
            </div>

            <p class="relative z-10 text-sm text-white/70">
                Cần tài khoản? Liên hệ quản trị hệ thống của công ty.
            </p>
        </aside>

        <!-- Form -->
        <main
            class="flex flex-1 items-center justify-center px-5 py-10 sm:px-10"
        >
            <div class="form-lg w-full max-w-[420px]">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span
                        class="flex size-11 items-center justify-center rounded-xl bg-primary text-base font-semibold tracking-wide text-primary-foreground"
                    >
                        TV
                    </span>
                    <span class="grid leading-tight">
                        <span class="text-lg font-semibold">Trường Vinh</span>
                        <span class="text-sm text-muted-foreground">
                            Kho giấy tờ
                        </span>
                    </span>
                </div>

                <div class="mb-8 grid gap-2">
                    <h1
                        class="text-[30px] leading-tight font-semibold tracking-tight"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="text-base leading-relaxed text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>
        </main>
    </div>
</template>
