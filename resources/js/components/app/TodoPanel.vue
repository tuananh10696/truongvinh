<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { ListChecks, Plus, X } from '@lucide/vue';
import { computed } from 'vue';
import TodoController from '@/actions/App/Http/Controllers/TodoController';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { formatShortDate, todayIso } from '@/lib/format';
import { cn } from '@/lib/utils';

type Todo = { id: number; title: string; dueOn: string | null; done: boolean };

const props = defineProps<{ todos: Todo[] }>();

const form = useForm({ title: '', due_on: '' });
const remaining = computed(
    () => props.todos.filter((todo) => !todo.done).length,
);

function dueLabel(todo: Todo): { text: string; tone: string } | null {
    if (todo.done) {
        return { text: 'Đã xong', tone: 'bg-accent text-accent-foreground' };
    }

    if (!todo.dueOn) {
        return null;
    }

    const today = todayIso();

    if (todo.dueOn === today) {
        return { text: 'Hôm nay', tone: 'bg-[#fff1d6] text-[#965006]' };
    }

    if (todo.dueOn < today) {
        return {
            text: `Quá hạn ${formatShortDate(todo.dueOn)}`,
            tone: 'bg-[#fde8e6] text-[#a1271d]',
        };
    }

    return {
        text: `Hạn ${formatShortDate(todo.dueOn)}`,
        tone: 'bg-muted text-[#3b4954]',
    };
}

function toggle(todo: Todo, done: boolean) {
    router.patch(
        TodoController.update.url(todo.id),
        { done },
        { preserveScroll: true },
    );
}

function remove(todo: Todo) {
    router.delete(TodoController.destroy.url(todo.id), {
        preserveScroll: true,
    });
}

function add() {
    form.transform((data) => ({ ...data, due_on: data.due_on || null })).submit(
        TodoController.store(),
        { preserveScroll: true, onSuccess: () => form.reset() },
    );
}
</script>

<template>
    <section class="grid gap-2 rounded-2xl border bg-card p-5">
        <div class="flex items-center justify-between gap-3">
            <h2 class="flex items-center gap-2.5 text-[17px] font-medium">
                <span
                    class="flex size-8 items-center justify-center rounded-[9px] bg-accent text-primary"
                >
                    <ListChecks class="size-[18px]" />
                </span>
                Việc cần làm
            </h2>
            <span class="text-[13px] text-muted-foreground">
                Còn {{ remaining }} việc
            </span>
        </div>

        <ul v-if="todos.length" class="grid">
            <li
                v-for="todo in todos"
                :key="todo.id"
                class="group flex items-start gap-3 border-b border-[#eef2f4] py-2.5"
            >
                <Checkbox
                    :id="`todo-${todo.id}`"
                    class="mt-0.5 size-5"
                    :model-value="todo.done"
                    @update:model-value="
                        (value) => toggle(todo, value === true)
                    "
                />
                <label
                    :for="`todo-${todo.id}`"
                    class="grid flex-1 cursor-pointer gap-1"
                >
                    <span
                        :class="
                            cn(
                                'leading-snug',
                                todo.done && 'text-[#8c9aa5] line-through',
                            )
                        "
                    >
                        {{ todo.title }}
                    </span>
                    <span
                        v-if="dueLabel(todo)"
                        :class="
                            cn(
                                'w-fit rounded-full px-2 py-0.5 text-xs font-medium',
                                dueLabel(todo)?.tone,
                            )
                        "
                    >
                        {{ dueLabel(todo)?.text }}
                    </span>
                </label>
                <button
                    type="button"
                    class="rounded-md p-1 text-muted-foreground opacity-60 hover:bg-muted hover:opacity-100 focus-visible:opacity-100"
                    :aria-label="`Xoá việc ${todo.title}`"
                    @click="remove(todo)"
                >
                    <X class="size-4" />
                </button>
            </li>
        </ul>
        <p v-else class="py-3 text-sm text-muted-foreground">
            Chưa có việc nào. Ghi lại việc cần làm để không quên.
        </p>

        <form class="mt-1 grid gap-2" @submit.prevent="add">
            <div class="flex gap-2">
                <Input
                    v-model="form.title"
                    aria-label="Việc cần làm mới"
                    placeholder="Thêm việc cần làm"
                    class="h-11 flex-1 bg-[#f9fbfc]"
                />
                <Button
                    type="submit"
                    size="icon-lg"
                    class="size-11"
                    aria-label="Thêm việc"
                    :disabled="form.processing || !form.title.trim()"
                >
                    <Plus class="size-5" />
                </Button>
            </div>
            <label
                v-if="form.title"
                class="flex items-center gap-2 text-sm text-muted-foreground"
            >
                Hạn (không bắt buộc)
                <input
                    v-model="form.due_on"
                    type="date"
                    class="h-9 rounded-md border border-input bg-transparent px-2 text-foreground"
                />
            </label>
            <p v-if="form.errors.title" class="text-sm text-destructive">
                {{ form.errors.title }}
            </p>
        </form>
    </section>
</template>
