<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import RecurringTaskController from '@/actions/App/Http/Controllers/RecurringTaskController';
import RecurringTaskDialog from '@/components/app/RecurringTaskDialog.vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { calendar } from '@/routes';

type CalendarEvent = {
    type: 'recurring' | 'todo';
    id: number;
    title: string;
    rule: string;
    done: boolean;
};

defineProps<{
    month: string;
    monthLabel: string;
    prevMonth: string;
    nextMonth: string;
    days: Array<{
        date: string;
        day: number;
        inMonth: boolean;
        isToday: boolean;
        events: CalendarEvent[];
    }>;
    tasks: Array<{ id: number; title: string; rule: string; daily: boolean }>;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Lịch công việc', href: calendar() }] },
});

const weekdays = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
const dialogOpen = ref(false);

function removeTask(id: number) {
    router.delete(RecurringTaskController.destroy.url(id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Lịch công việc" />

    <div
        class="mx-auto flex w-full max-w-[1240px] flex-col gap-6 px-4 py-6 md:px-10 md:py-8"
    >
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <Button variant="outline" size="icon-lg" as-child>
                    <Link
                        :href="calendar({ query: { month: prevMonth } })"
                        aria-label="Tháng trước"
                    >
                        <ChevronLeft />
                    </Link>
                </Button>
                <h1 class="min-w-48 text-center text-2xl font-medium">
                    {{ monthLabel }}
                </h1>
                <Button variant="outline" size="icon-lg" as-child>
                    <Link
                        :href="calendar({ query: { month: nextMonth } })"
                        aria-label="Tháng sau"
                    >
                        <ChevronRight />
                    </Link>
                </Button>
            </div>
            <Button size="lg" class="h-11" @click="dialogOpen = true">
                <Plus />
                Tạo việc định kỳ
            </Button>
        </div>

        <div class="flex flex-wrap items-start gap-6">
            <div
                class="min-w-0 flex-[999] basis-[600px] overflow-x-auto rounded-2xl border bg-card"
            >
                <div class="grid min-w-[640px] grid-cols-7">
                    <span
                        v-for="weekday in weekdays"
                        :key="weekday"
                        class="border-b px-3 py-2 text-[13px] text-muted-foreground"
                    >
                        {{ weekday }}
                    </span>
                    <div
                        v-for="day in days"
                        :key="day.date"
                        :class="
                            cn(
                                'min-h-[108px] border-r border-b border-[#eef2f4] p-2',
                                !day.inMonth && 'bg-[#f9fbfc] text-[#9aa7b1]',
                            )
                        "
                    >
                        <span
                            :class="
                                cn(
                                    'inline-flex size-8 items-center justify-center rounded-full text-[15px] font-medium',
                                    day.isToday &&
                                        'bg-primary font-medium text-primary-foreground',
                                )
                            "
                        >
                            {{ day.day }}
                        </span>
                        <ul class="mt-1 grid gap-1">
                            <li
                                v-for="event in day.events"
                                :key="`${event.type}-${event.id}`"
                                :class="
                                    cn(
                                        'line-clamp-2 rounded-md px-2 py-1 text-[13px] leading-snug font-medium',
                                        event.type === 'recurring'
                                            ? 'bg-accent text-accent-foreground'
                                            : 'bg-[#e5f0ff] text-[#1f55a8]',
                                        event.done && 'line-through opacity-60',
                                    )
                                "
                                :title="`${event.title} (${event.rule})`"
                            >
                                {{ event.title }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <section
                class="grid w-full flex-1 basis-[300px] gap-3 rounded-2xl border bg-card p-5 lg:max-w-[340px]"
            >
                <div class="grid gap-1">
                    <h2 class="text-lg font-semibold">Việc định kỳ của tôi</h2>
                    <p class="text-sm text-muted-foreground">
                        Việc hằng ngày không hiện trong ô lịch để lịch gọn hơn.
                    </p>
                </div>
                <ul v-if="tasks.length" class="grid">
                    <li
                        v-for="task in tasks"
                        :key="task.id"
                        class="flex items-center gap-3 border-t border-[#eef2f4] py-2.5"
                    >
                        <span class="grid flex-1">
                            <span class="text-[15px] font-medium">{{
                                task.title
                            }}</span>
                            <span
                                :class="[
                                    'w-fit text-[13px]',
                                    task.daily
                                        ? 'mt-1 rounded-full bg-accent px-2 py-0.5 font-medium text-accent-foreground'
                                        : 'text-muted-foreground',
                                ]"
                            >
                                {{ task.rule }}
                            </span>
                        </span>
                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="`Xoá việc ${task.title}`"
                            @click="removeTask(task.id)"
                        >
                            <Trash2 />
                        </Button>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    Chưa có việc định kỳ.
                </p>
            </section>
        </div>
    </div>

    <RecurringTaskDialog v-model:open="dialogOpen" />
</template>
