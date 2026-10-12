<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Check, History, Plus } from '@lucide/vue';
import { ref } from 'vue';
import RecurringTaskController from '@/actions/App/Http/Controllers/RecurringTaskController';
import RecurringTaskDialog from '@/components/app/RecurringTaskDialog.vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { calendar } from '@/routes';

type WeekDay = {
    date: string;
    day: number;
    weekday: string;
    isToday: boolean;
    busy: boolean;
};

type Upcoming = {
    id: number;
    title: string;
    rule: string;
    dueOn: string;
    day: string;
    month: string;
    isToday: boolean;
};

defineProps<{ week: WeekDay[]; upcoming: Upcoming[] }>();

const dialogOpen = ref(false);

function complete(item: Upcoming) {
    router.post(
        RecurringTaskController.complete.url(item.id),
        { due_on: item.dueOn },
        { preserveScroll: true },
    );
}
</script>

<template>
    <section class="grid gap-3.5 rounded-2xl border bg-card p-5">
        <div class="flex items-center justify-between gap-3">
            <h2 class="flex items-center gap-2.5 text-[17px] font-medium">
                <span
                    class="flex size-8 items-center justify-center rounded-[9px] bg-[#fff1d6] text-[#b45309]"
                >
                    <History class="size-[18px]" />
                </span>
                Việc định kỳ
            </h2>
            <Link :href="calendar()" class="text-sm font-medium text-primary">
                Xem lịch
            </Link>
        </div>

        <div class="grid grid-cols-7 gap-1" aria-label="Tuần này">
            <span
                v-for="day in week"
                :key="day.date"
                :class="
                    cn(
                        'flex flex-col items-center gap-1 rounded-[10px] pt-2 pb-1.5',
                        day.isToday
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-background',
                    )
                "
                :aria-current="day.isToday ? 'date' : undefined"
            >
                <span class="text-[11px] opacity-85">{{ day.weekday }}</span>
                <span class="text-[15px] font-medium">{{ day.day }}</span>
                <span
                    :class="
                        cn(
                            'size-[5px] rounded-full',
                            day.busy
                                ? day.isToday
                                    ? 'bg-primary-foreground'
                                    : 'bg-primary'
                                : 'bg-transparent',
                        )
                    "
                />
                <span v-if="day.busy" class="sr-only">Có việc</span>
            </span>
        </div>

        <ul v-if="upcoming.length" class="grid">
            <li
                v-for="item in upcoming"
                :key="`${item.id}-${item.dueOn}`"
                class="flex items-center gap-3 border-t border-[#eef2f4] py-2"
            >
                <span
                    :class="
                        cn(
                            'flex w-11 shrink-0 flex-col items-center rounded-[10px] py-1 leading-tight',
                            item.isToday ? 'bg-accent' : 'bg-background',
                        )
                    "
                >
                    <span class="text-base font-medium text-primary">{{
                        item.day
                    }}</span>
                    <span class="text-[11px] text-muted-foreground">{{
                        item.month
                    }}</span>
                </span>
                <span class="grid min-w-0 flex-1">
                    <span class="text-sm font-medium">{{ item.title }}</span>
                    <span class="text-[13px] font-light text-muted-foreground">
                        {{ item.rule }}
                    </span>
                </span>
                <Button
                    v-if="item.isToday"
                    variant="secondary"
                    size="sm"
                    class="bg-accent text-accent-foreground hover:bg-accent/80"
                    @click="complete(item)"
                >
                    <Check />
                    Xong
                </Button>
            </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">
            Chưa có việc định kỳ. Ví dụ: gửi phiếu thu ngày 5 hằng tháng.
        </p>

        <Button
            variant="outline"
            class="h-11 border-dashed text-accent-foreground"
            @click="dialogOpen = true"
        >
            <Plus />
            Tạo việc định kỳ
        </Button>
    </section>

    <RecurringTaskDialog v-model:open="dialogOpen" />
</template>
