<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        extension: string;
        size?: 'sm' | 'md';
    }>(),
    { size: 'md' },
);

const tone = computed(() => {
    switch (props.extension) {
        case 'doc':
        case 'docx':
        case 'odt':
        case 'rtf':
            return 'docx';
        case 'xls':
        case 'xlsx':
        case 'ods':
        case 'csv':
            return 'xlsx';
        case 'pdf':
            return 'pdf';
        default:
            return 'other';
    }
});
</script>

<template>
    <span
        aria-hidden="true"
        :class="
            cn(
                'flex shrink-0 items-center justify-center rounded-[10px] font-semibold tracking-wide uppercase',
                size === 'md' ? 'size-11 text-[11px]' : 'size-8 text-[10px]',
            )
        "
        :style="{
            backgroundColor: `var(--file-${tone}-bg)`,
            color: `var(--file-${tone}-fg)`,
        }"
    >
        {{ extension.slice(0, 4) || 'FILE' }}
    </span>
</template>
