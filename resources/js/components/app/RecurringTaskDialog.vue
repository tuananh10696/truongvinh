<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import RecurringTaskController from '@/actions/App/Http/Controllers/RecurringTaskController';
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

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    title: '',
    frequency: 'monthly',
    weekday: '1',
    day_of_month: '5',
    time_of_day: '',
});

const frequencies = [
    { value: 'daily', label: 'Hằng ngày' },
    { value: 'weekly', label: 'Hằng tuần' },
    { value: 'monthly', label: 'Hằng tháng' },
];

const weekdays = [
    { value: '1', label: 'Thứ Hai' },
    { value: '2', label: 'Thứ Ba' },
    { value: '3', label: 'Thứ Tư' },
    { value: '4', label: 'Thứ Năm' },
    { value: '5', label: 'Thứ Sáu' },
    { value: '6', label: 'Thứ Bảy' },
    { value: '7', label: 'Chủ nhật' },
];

const selectClass =
    'h-11 w-full rounded-md border border-input bg-transparent px-3 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

watch(open, (isOpen) => {
    if (isOpen) {
        form.reset();
        form.clearErrors();
    }
});

function submit() {
    form.transform((data) => ({
        title: data.title,
        frequency: data.frequency,
        weekday: data.frequency === 'weekly' ? Number(data.weekday) : null,
        day_of_month:
            data.frequency === 'monthly' ? Number(data.day_of_month) : null,
        time_of_day: data.time_of_day || null,
    })).submit(RecurringTaskController.store(), {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Tạo việc định kỳ</DialogTitle>
                    <DialogDescription>
                        Việc sẽ tự hiện ở trang chủ khi đến hạn. Chỉ bạn thấy.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="recurring-title">Tên việc</Label>
                    <Input
                        id="recurring-title"
                        v-model="form.title"
                        class="h-11"
                        placeholder="Ví dụ: Gửi phiếu thu cho khách hàng"
                        required
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <fieldset class="grid gap-2">
                    <legend class="mb-2 text-sm font-medium">Lặp lại</legend>
                    <div class="grid grid-cols-3 gap-2">
                        <label
                            v-for="item in frequencies"
                            :key="item.value"
                            :class="
                                cn(
                                    'flex h-11 cursor-pointer items-center justify-center rounded-lg border text-sm transition-colors',
                                    form.frequency === item.value
                                        ? 'border-primary bg-accent font-medium text-accent-foreground'
                                        : 'hover:bg-muted',
                                )
                            "
                        >
                            <input
                                v-model="form.frequency"
                                type="radio"
                                :value="item.value"
                                class="sr-only"
                            />
                            {{ item.label }}
                        </label>
                    </div>
                </fieldset>

                <div v-if="form.frequency === 'weekly'" class="grid gap-2">
                    <Label for="recurring-weekday">Vào ngày</Label>
                    <select
                        id="recurring-weekday"
                        v-model="form.weekday"
                        :class="selectClass"
                    >
                        <option
                            v-for="day in weekdays"
                            :key="day.value"
                            :value="day.value"
                        >
                            {{ day.label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.weekday" />
                </div>

                <div v-if="form.frequency === 'monthly'" class="grid gap-2">
                    <Label for="recurring-day">Vào ngày trong tháng</Label>
                    <select
                        id="recurring-day"
                        v-model="form.day_of_month"
                        :class="selectClass"
                    >
                        <option
                            v-for="day in 31"
                            :key="day"
                            :value="String(day)"
                        >
                            Ngày {{ day }}
                        </option>
                    </select>
                    <p class="text-xs text-muted-foreground">
                        Tháng nào không có ngày này thì nhắc vào ngày cuối
                        tháng.
                    </p>
                    <InputError :message="form.errors.day_of_month" />
                </div>

                <div class="grid gap-2">
                    <Label for="recurring-time">Giờ (không bắt buộc)</Label>
                    <input
                        id="recurring-time"
                        v-model="form.time_of_day"
                        type="time"
                        :class="selectClass"
                    />
                    <InputError :message="form.errors.time_of_day" />
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
                        Tạo việc định kỳ
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
