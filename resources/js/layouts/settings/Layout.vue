<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { KeyRound, UserRound } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn, toUrl } from '@/lib/utils';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

// Giao diện chỉ có chế độ sáng nên không hiện mục "Giao diện" của starter kit.
const sidebarNavItems: NavItem[] = [
    { title: 'Thông tin cá nhân', href: editProfile(), icon: UserRound },
    { title: 'Mật khẩu và bảo mật', href: editSecurity(), icon: KeyRound },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div
        class="mx-auto flex w-full max-w-[1100px] flex-col gap-8 px-4 py-6 md:px-10 md:py-8"
    >
        <div class="grid gap-1.5">
            <h1 class="text-[32px] leading-tight font-medium tracking-tight">
                Cài đặt tài khoản
            </h1>
            <p class="font-light text-muted-foreground">
                Thông tin cá nhân, mật khẩu và bảo mật đăng nhập
            </p>
        </div>

        <div class="flex flex-col gap-8 lg:flex-row lg:gap-12">
            <nav
                class="flex gap-2 overflow-x-auto lg:w-60 lg:shrink-0 lg:flex-col"
                aria-label="Cài đặt"
            >
                <Link
                    v-for="item in sidebarNavItems"
                    :key="toUrl(item.href)"
                    :href="item.href"
                    :class="
                        cn(
                            'flex h-11 shrink-0 items-center gap-3 rounded-lg px-3 text-base font-medium transition-colors',
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-accent font-semibold text-accent-foreground'
                                : 'text-sidebar-foreground hover:bg-muted',
                        )
                    "
                >
                    <component :is="item.icon" class="size-5" />
                    {{ item.title }}
                </Link>
            </nav>

            <div
                class="form-lg flex-1 rounded-2xl border bg-card p-5 md:max-w-2xl md:p-8"
            >
                <section class="space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
